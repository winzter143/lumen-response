<?php

namespace F3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use F3\Helpers\PdfHelper;

class PdfMerger implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $fileKey, $now;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileKey, $now)
    {
        $this->fileKey = $fileKey;
        $this->now = $now;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->mergePdfs('awb','TrackingFile','P');
        $this->mergePdfs('pod','POD','L');
    }

    private function mergePdfs($type,$filename,$orientation) 
    {
        Log::info("Merging stickers");
        $pdf = new \LynX39\LaraPdfMerger\PdfManage;

        if((new \FilesystemIterator(dirname(__DIR__,2) . '/resources/tmp'))->valid()) { 
            foreach (new \FilesystemIterator(dirname(__DIR__,2) . '/resources/tmp', \FilesystemIterator::SKIP_DOTS) as $file) {
                if ($file->isFile() && (strpos( $file->getFilename() , $type.'-'.$this->fileKey ) !== false) ) {
                    Log::info($file->getFilename());
                    $pdf->addPdf(dirname(__DIR__,2) . '/resources/tmp/'. $file->getFilename(), 'all');
                }
            }

            $resDir = dirname(__DIR__,2) . '/resources/pdf/';

            $pdf->merge('file', $resDir . "/$type-" . $this->now . '.pdf', $orientation);
            Log::info("Merged stickers");

            //Delete temp files
            $mask = dirname(__DIR__,2) . "/resources/tmp/$type-" . $this->fileKey . '*.pdf';
            array_map('unlink',glob($mask));

            //Push file to S3
            $pdfHelp = new PdfHelper();
            $pdfHelp->putObjectToS3($resDir . "$type-" . $this->now . '.pdf', "Test-$filename-" . $this->now . '.pdf');

            //Delete temp files
            gc_collect_cycles();
            $mask = $resDir . "$type-" . $this->now . '.pdf';
            array_map('unlink',glob($mask));
        } else {
            return false;
        }
    }
}
