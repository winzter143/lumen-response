<?php

namespace F3\Helpers;

use F3\Models\Address;
use DNS1D;
use F3\Jobs\PdfBuilder;
use F3\Jobs\PdfMerger;
use Storage;
use Illuminate\Support\Facades\Log;
use PDF;

class PdfHelper
{	    

    public function buildPdf($orders,$batch=false)
    {
        if ($batch) {
            // Pass filters
            return $this->buildBatch($orders);
        } else {
            // Pass order
            return $this->buildOne($orders);
        }
    }

    private function buildOne($order)
    {
        $now = date_format(new \DateTime(),"YmdHis");
        $resDir = app()->basePath() . '/storage/pdf/';

        // Build order sticker
        $awb = $this->buildAwb($order);
        $pod = $this->buildPod($order);

        $htmls = $this->prepareHtml($awb, $pod);

        $this->generateAwbPdf($htmls['awb'], 1, $resDir, $now);
        $this->generatePodPdf($htmls['pod'], 1, $resDir, $now);

        /*file_put_contents(\Config::get('view.paths')[0] . '/awb.html', $awb);
        file_put_contents(\Config::get('view.paths')[0] . '/pod.html', $pod);*/
        
        // Put files to s3, returns url
        $url = array('awb' => $this->putObjectToS3($resDir . "awb-$now-1.pdf", "Test-TrackingFile-$now.pdf"),
                'pod' => $this->putObjectToS3($resDir . "pod-$now-1.pdf", "Test-POD-$now.pdf")
            );

        // Delete temp files
        gc_collect_cycles();
        unlink($resDir . "awb-$now-1.pdf");
        unlink($resDir . "pod-$now-1.pdf");

        return response()->json($url);
    }

    public function buildBatch($filters)
    {           
        $now = date_format(new \DateTime(),"YmdHis");
        $resDir = app()->basePath() . '/storage/pdf/';

        // Create temporary file
        file_put_contents($resDir . "awb-$now.pdf", '');
        file_put_contents($resDir . "pod-$now.pdf", '');

        $fileKey = sha1(time());

        $this->dispatch(new PdfBuilder($filters,$fileKey,$now));

        // Put temp files to s3, returns url
        $url = array('awb' => $this->putObjectToS3($resDir . "awb-$now.pdf", "Test-TrackingFile-$now.pdf"),
                'pod' => $this->putObjectToS3($resDir . "pod-$now.pdf", "Test-POD-$now.pdf")
            );

        return response()->json($url);
    }

    /**
     * Generate awb html based on awb templates
     * HTML output
    **/
    public function buildAwb($order)
	{	
		$template = file_get_contents( dirname(__DIR__) . '/../resources/templates/awb-body.html' );

		// Generate the partner barcode (1D).
        $barcode = DNS1D::getBarcodeHTML($order['tracking_number'], 'C128', 2, 60);

        if($order['total']) {
        	$codClass = '';
            $codAmount = number_format(floatval(str_replace(',','',$order['total'])), 2);
        } else {
        	$codClass = 'hide';
            $codAmount = 0;
        }

        // Build the template.
        return str_replace([
            //'{qr_code}',
            '{partner_barcode}',
            '{tracking_number}',
            '{partner_tracking_number}',
            '{seller_name}',
            '{seller_address}',
            '{seller_city}',
            '{seller_contact_number}',
            '{buyer_name}',
            '{buyer_address}',
            '{buyer_state}',
            '{buyer_city}',
	    	'{buyer_area}',
            '{buyer_contact_number}',
            '{quantity}',
            '{cod_fee}',
            '{origin}',
            '{client}',
            '{reference_no}',
            '{cod_class}',
			'{txn_date}'
        ], [
            //$qr_code,
            $barcode,
            $order['tracking_number'],
            $order['tracking_number'],
            $order['pickup_address']['name'],
            Address::format($order['pickup_address'], ', '),
            $order['pickup_address']['city'],
            $order['pickup_address']['mobile_number'],
            $order['buyer_name'],
	    	Address::format($order['delivery_address'], ', '),
            $order['delivery_address']['state'],
            $order['delivery_address']['city'],
            'Area',
            $order['delivery_address']['mobile_number'],
            'Quantity',
            $codAmount,
            'Origin',
            'Client',
            'Ref number',
            $codClass,
			$order['created_at']
        ], $template);
	}

    /**
     * Generate pod html based on pod templates
     * HTML output
    **/
	public function buildPod($order)
	{	
		$template = file_get_contents( dirname(__DIR__) . '/../resources/templates/pod-body.html' );

		// Generate the partner barcode (1D).
        $barcode = DNS1D::getBarcodeHTML($order['tracking_number'], 'C128', 2, 70);

        if($order['total']) {
        	$codClass = 'trans-type';
			$codAmount = number_format(floatval(str_replace(',','',$order['total'])), 2);
        } else {
        	$codClass = 'hide';
			$codAmount = 0;
        }

        // Build the template.  
        return str_replace([
            //'{qr_code}',
            '{partner_barcode}',
            '{tracking_number}',
            '{partner_tracking_number}',
            '{seller_name}',
            '{seller_address}',
            '{seller_city}',
            '{seller_contact_number}',
            '{buyer_name}',
            '{buyer_address}',
            '{buyer_state}',
            '{buyer_city}',
            '{buyer_area}',
            '{buyer_contact_number}',
            '{quantity}',
            '{cod_fee}',
            '{origin}',
            '{client}',
            '{reference_no}',
            '{cod_class}',
            '{txn_date}'
        ], [
            //$qr_code,
            $barcode,
            $order['tracking_number'],
            $order['tracking_number'],
            $order['pickup_address']['name'],
            Address::format($order['pickup_address'], ', '),
            $order['pickup_address']['city'],
            $order['pickup_address']['mobile_number'],
            $order['buyer_name'],
            Address::format($order['delivery_address'], ', '),
            $order['delivery_address']['state'],
            $order['delivery_address']['city'],
            'Area',
            $order['delivery_address']['mobile_number'],
            'Quantity',
            $codAmount,
            'Origin',
            'Client',
            'Ref number',
            $codClass,
            $order['created_at']
        ], $template);
	}

    public function putObjectToS3($source, $key)
    {
        // Put file to s3
        $s3 = \Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $command = $client->putObject([
            'Bucket'        => \Config::get('filesystems.disks.s3.bucket'),
            'Key'           => $key,
            'SourceFile'    => $source,
            'ACL'           => 'public-read'
        ]);

        return $command['ObjectURL'];
    }

    public function preparePdf($fileKey,$orders)
    {
        Log::info('Start building..');
        $awbs = [];
        $pods = [];

        $first = $orders->first()['id'];

        foreach ($orders as $order) {
            $awbs[] = $this->buildAwb($order);
            $pods[] = $this->buildPod($order);
        }

        $awbs = implode('', $awbs);
        $pods = implode('', $pods);

        $dir = dirname(__DIR__) . '/../resources/tmp/';
        $htmls = $this->prepareHtml($awbs, $pods);

        //Generate AWB and POD pdfs
        $this->generateAwbPdf($htmls['awb'], $first, $dir, $fileKey);
        $this->generatePodPdf($htmls['pod'], $first, $dir, $fileKey);
    }

    public function prepareHtml($awbs, $pods)
    {
        //Add awb view styles
        $styles = file_get_contents( dirname(__DIR__) . '/../resources/templates/awb-style.html');
        $awbs =  $styles . $awbs . '</body></html>';

        //Add pod view styles
        $styles = file_get_contents( dirname(__DIR__) . '/../resources/templates/pod-style.html');
        $pods =  $styles . $pods . '</body></html>';

        return ['awb' => $awbs, 'pod' => $pods];
    }

    public function generateAwbPdf($awbs,$first=1,$dir,$fileKey)
    {
        Log::info('Generating AWB PDF..');
        
        $pdf = PDF::loadHTML($awbs);
        $pdf->setOption('orientation', 'Portrait');
        $pdf->setOption('page-width', 110);
        $pdf->setOption('page-height', 165);
        $filename = $dir . 'awb-' .$fileKey . "-$first.pdf";
        $pdf->save($filename);
        
        Log::info('PDF Generated', array('file' => $filename));
    }

    public function generatePodPdf($pods,$first=1,$dir,$fileKey)
    {
        Log::info('Generating POD PDF..');
        
        $pdf = PDF::loadHTML($pods);
        $pdf->setOption('orientation', 'Landscape');
        $pdf->setOption('page-width', 110);
        $pdf->setOption('page-height', 165);
        $filename = $dir . 'pod-' .$fileKey . "-$first.pdf";
        $pdf->save($filename);
        
        Log::info('PDF Generated', array('file' => $filename));
    }
}