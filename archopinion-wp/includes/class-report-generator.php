<?php

// Ensure TCPDF is loaded. Adjust the path as necessary if using a different structure.
// This assumes TCPDF is installed via Composer or manually in a 'vendor' directory.
if (file_exists(ARCHOPINION_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once ARCHOPINION_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
} elseif (class_exists('TCPDF')) {
    // TCPDF is already loaded (e.g., by another plugin or theme)
} else {
    // Fallback or error if TCPDF is not found
    // For now, we'll let it fail if not present, but a real plugin might handle this more gracefully.
}


class ArchOpinion_Report_Generator {

    public function generate_pdf_report( $analysis_data, $image_path ) {
        if ( ! class_exists( 'TCPDF' ) ) {
            return new WP_Error('tcpdf_missing', 'TCPDF library is not available. Please install it.');
        }

        try {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('ArchOpinion Plugin');
            $pdf->SetTitle('Architectural Analysis Report');
            $pdf->SetSubject('Analysis Report');

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Title
            $pdf->Write(0, 'Architectural Analysis Report', '', 0, 'C', true, 0, false, false, 0);
            $pdf->Ln(10);

            // Analysis Section
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Write(0, 'Analysis:', '', 0, 'L', true, 0, false, false, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Write(0, $analysis_data, '', 0, 'L', true, 0, false, false, 0);
            $pdf->Ln(10);

            // Image Section
            if ( $image_path && file_exists( $image_path ) ) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Write(0, 'Analyzed Image:', '', 0, 'L', true, 0, false, false, 0);
                // Embed image. Adjust X, Y, W, H, Type, Link, Align, Resize, DPI, PAlign, IsMask, ImgMask, Border, Fitbox, Hidden, Fitonpage
                // The image function in TCPDF can be tricky with paths and types.
                // Using '@' to suppress errors from getimagesize if the image is invalid, and then checking the result.
                $image_type = '';
                if (function_exists('exif_imagetype')) {
                    $image_type_constant = @exif_imagetype($image_path);
                    if ($image_type_constant === IMAGETYPE_GIF) $image_type = 'GIF';
                    elseif ($image_type_constant === IMAGETYPE_JPEG) $image_type = 'JPEG';
                    elseif ($image_type_constant === IMAGETYPE_PNG) $image_type = 'PNG';
                    // Add more types if needed
                } else {
                    // Fallback if exif_imagetype is not available (less reliable)
                    $image_info = @getimagesize($image_path);
                    if ($image_info !== false) {
                        $image_type = strtoupper(str_replace('image/', '', $image_info['mime']));
                    }
                }

                if ($image_type) {
                     // Adjust width and height as needed, or let TCPDF auto-size
                    $pdf->Image($image_path, '', '', 150, 0, $image_type, '', 'T', false, 300, '', false, false, 0, false, false, false);
                } else {
                     $pdf->Write(0, '[Image could not be displayed - unsupported format or error reading file]', '', 0, 'L', true);
                }
                $pdf->Ln(10);
            } else {
                $pdf->Write(0, '[Image not available or path incorrect]', '', 0, 'L', true);
                $pdf->Ln(10);
            }

            // Output the PDF
            // 'D' means download the PDF directly
            // 'S' returns the PDF as a string
            // 'F' saves to a local file
            // 'I' sends inline to the browser
            $report_content = $pdf->Output('ArchOpinion_Report.pdf', 'S');
            return $report_content;

        } catch (Exception $e) {
            // Log the exception message or handle it as needed
            return new WP_Error('pdf_generation_failed', 'Failed to generate PDF report: ' . $e->getMessage());
        }
    }
}
