<?php

namespace App\Service;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoiceService
{
    public function __construct(
        private readonly Environment $twig
    ) {}

    public function generateInvoicePdf(Order $order): string
    {
        // Configure Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isRemoteEnabled', true); // Allow loading images if needed

        $dompdf = new Dompdf($pdfOptions);

        // Render HTML from Twig template
        $html = $this->twig->render('invoice/invoice.html.twig', [
            'order' => $order
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Return PDF binary content
        return $dompdf->output();
    }
}
