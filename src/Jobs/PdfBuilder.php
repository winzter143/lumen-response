<?php

namespace F3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use F3\Helpers\PdfHelper;
use F3\Jobs\PdfMerger;
use PDF;
use F3\Components\JWT;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class PdfBuilder implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;
    protected $fileKey;
    protected $now;
    //protected $orders;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filters,$fileKey,$now)
    {
        $this->filters = $filters;
        $this->fileKey = $fileKey;
        $this->now = $now;
        //$this->orders = $orders;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Consume getOrders endpoint
        $orders = collect($this->getOrders());

        // Process pdf generation by chunks
        foreach ($orders->chunk(20) as $chunk) {
            $pdfHelp = new PdfHelper();
            $pdfHelp->preparePdf($this->fileKey, $chunk);
        }

        // Merge all generated PDFs
        dispatch(new PdfMerger($this->fileKey,$this->now));
    }

    public function getOrders()
    {
        $client = new Client([
            'base_uri' => env('ORDERS_URL')
        ]); 
        
        $response = $client->request('GET', 'orders', [
            'headers' => [
                'Authorization' => 'Bearer '. $this->getToken()
            ],
            'query' => [
                    'start_date' => $this->filters['start_date'],
                    'end_date' => $this->filters['end_date'],
                    'per_page' => 0,
                    'extended' => 1
                ]
            //'debug' => true
        ]);

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    private function getToken()
    {
        try {
            // Create the token.
            $jwt = JWT::createToken([
                'iat' => time(),
                'sub' => env('SUB')
            ], env('SECRET_KEY'));
            
            return $jwt;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
