<?php
require_once 'vendor/autoload.php'; 
require('./database.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// --- 1. Receive Selected IDs via POST ---
$selectedIds = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $idsJson = $_POST['ids'];
    $decodedIds = json_decode($idsJson, true);
    if (is_array($decodedIds)) {
        $selectedIds = array_map('intval', $decodedIds); // Sanitize IDs
    }
}

if (empty($selectedIds)) {
    echo "<h1>No Purchase Requests Selected for Export.</h1>";
    echo "<p>Please go back and select one or more purchase requests to export.</p>";
    exit();
}

$idsString = implode(',', $selectedIds);

// --- 2. Fetch Detailed Data for Selected Purchase Requests and Their Items ---
$purchaseRequests = [];

// Fetch main purchase request details. 'approved_by' column removed from SELECT query.
$queryMain = "SELECT id, pr_number, purpose, requested_by, status, date_prepared, date_needed, location, previous_status
              FROM purchase_requests_tbl
              WHERE id IN ($idsString)
              ORDER BY date_prepared DESC";
$resultMain = mysqli_query($connection, $queryMain);

if (!$resultMain) {
    die('Error fetching main purchase request data: ' . mysqli_error($connection));
}

while ($rowMain = mysqli_fetch_assoc($resultMain)) {
    $purchaseRequests[$rowMain['id']] = $rowMain;
    $purchaseRequests[$rowMain['id']]['items'] = []; // Initialize items array for each PR
}

// Fetch items for all selected purchase requests
$queryItems = "SELECT purchase_request_id, qty, unit, item_description, remarks
               FROM purchase_request_items_tbl
               WHERE purchase_request_id IN ($idsString)
               ORDER BY id ASC";
$resultItems = mysqli_query($connection, $queryItems);

if (!$resultItems) {
    die('Error fetching purchase request items data: ' . mysqli_error($connection));
}

while ($rowItem = mysqli_fetch_assoc($resultItems)) {
    $prId = $rowItem['purchase_request_id'];
    if (isset($purchaseRequests[$prId])) {
        $purchaseRequests[$prId]['items'][] = $rowItem;
    }
}

// Sort purchase requests by date_prepared for consistent output order
usort($purchaseRequests, function($a, $b) {
    return strtotime($b['date_prepared']) - strtotime($a['date_prepared']);
});


// --- 3. Generate HTML Content for PDF ---
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Request Details Report</title>
    <style>
        body {
            font-family: "Helvetica Neue", "Helvetica", Arial, sans-serif;
            font-size: 10pt;
            margin: 20mm;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }
        .header img {
            max-width: 100px;
            height: auto;
            margin-bottom: 10px;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 20pt;
            font-family: "Inter", sans-serif; /* Added font-family */
            font-weight: bold; /* Ensure bold */
        }
        .header h2 {
            color: #343a40;
            margin: 5px 0 0 0;
            font-size: 16pt;
        }

        .pr-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .pr-title {
            background-color: #28a745;
            color: #fff;
            padding: 10px 15px;
            margin: -20px -20px 15px -20px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .pr-title h2 {
            margin: 0;
            font-size: 16pt;
        }

        /* Custom Form-like Layout */
        .form-row {
            margin-bottom: 10px;
            font-size: 0; /* To collapse whitespace between inline-block elements */
        }
        .form-field {
            display: inline-block;
            width: 49%; /* Adjusted width for inline-block */
            vertical-align: top;
            font-size: 10pt; /* Reset font-size from parent */
            box-sizing: border-box;
            padding-right: 1%; /* Small gap between fields */
        }
        .form-field:last-child {
            padding-right: 0; /* No right padding for the last field in a row */
        }
        .form-field label {
            font-weight: bold;
            display: inline; /* Make label inline */
            margin-right: 5px; /* Space between label and value */
        }
        .form-field .value-display { /* New class for the value */
            display: inline; /* Make value inline */
            /* Removed border, padding, background for textbox look */
        }
        .status-badge {
            display: inline; /* Changed to inline to flow naturally with text */
            padding: 0; /* Removed padding */
            background-color: transparent; /* Ensure no background color */
            color: #343a40; /* Ensure text is dark and visible */
            text-align: left; /* Aligned with text */
            white-space: nowrap;
            vertical-align: baseline; /* Align with text baseline */
            border-radius: 0; /* Removed border-radius */
            border: none; /* Removed border */
        }

        /* Items table styling */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        /* Signature Section - Using CSS Table for reliable alignment */
        .signature-section {
            margin-top: 40px;
            width: 100%;
            display: table; /* Make it behave like a table */
            table-layout: fixed; /* Fix column widths */
        }
        .signature-row {
            display: table-row;
        }
        .signature-cell {
            display: table-cell;
            width: 50%; /* Each cell takes half width */
            padding: 0 10px; /* Add horizontal padding */
            box-sizing: border-box;
            vertical-align: top; /* Align content at the top of the cell */
            font-size: 10pt;
            text-align: left; /* Default to left alignment for content within cell */
        }
        .signature-cell.right-align { /* For the Approved By cell */
            padding-left: 0; /* Remove left padding for right-aligned cell */
            padding-right: 10px; /* Keep right padding */
        }

        .signature-label-and-name { /* Container for label and name on one line */
            display: flex; /* Changed to flex for better control */
            align-items: baseline; /* Align text baselines */
        }
        .signature-cell.right-align .signature-label-and-name {
            justify-content: flex-end; /* Align content to the right within the right cell */
        }

        .signature-label-text { /* For "Requested by:" and "Approved by:" */
            font-weight: bold;
            margin-right: 5px; /* Space between label and name */
            white-space: nowrap; /* Prevent label from wrapping */
        }
        /* Removed .signature-name-display as a separate span */
        /* The actual name will be directly in the .signature-label-and-name div */


        .page-break {
            page-break-after: always;
        }
        .footer {
            text-align: center;
            font-size: 8pt;
            color: #6c757d;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        /* Bootstrap-like colors (these will no longer apply to status-badge background) */
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; }
        .bg-danger { background-color: #dc3545; }
        .bg-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <img src="LOGO.png" alt="Viking Logo" style="width: 50px; height: 50px;">
        <h1>Viking Construction & Supplies</h1>
    </div>';

if (!empty($purchaseRequests)) {
    $counter = 0;
    foreach ($purchaseRequests as $pr) {
        $counter++;
        $status = htmlspecialchars($pr['status']);
        $badgeClass = ''; 
        
        // approvedBy will remain blank as the column is not fetched
        $approvedBy = ''; 

        $html .= '
        <div class="pr-container">
            <div class="pr-title">
                <h2>Purchase Request Details</h2>
            </div>
            <div class="pr-details">
                <div class="form-row">
                    <div class="form-field">
                        <label>Date Prepared:</label>
                        <span class="value-display">' . htmlspecialchars($pr['date_prepared']) . '</span>
                    </div>
                    <div class="form-field">
                        <label>PR No.:</label>
                        <span class="value-display">' . htmlspecialchars($pr['pr_number']) . '</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Purpose:</label>
                        <span class="value-display">' . htmlspecialchars($pr['purpose']) . '</span>
                    </div>
                    <div class="form-field">
                        <label>Date Needed:</label>
                        <span class="value-display">' . htmlspecialchars($pr['date_needed']) . '</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Location:</label>
                        <span class="value-display">' . htmlspecialchars($pr['location']) . '</span>
                    </div>
                    <div class="form-field">
                        <label>Status:</label>
                        <span class="value-display"><span class="status-badge">' . $status . '</span></span>
                    </div>
                </div>
            </div>';

        if (!empty($pr['items'])) {
            $html .= '
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Description</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($pr['items'] as $item) {
                $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['qty']) . '</td>
                        <td>' . htmlspecialchars($item['unit']) . '</td>
                        <td>' . htmlspecialchars($item['item_description']) . '</td>
                        <td>' . htmlspecialchars($item['remarks']) . '</td>
                    </tr>';
            }
            $html .= '
                </tbody>
            </table>';
        } else {
            $html .= '<p>No items associated with this purchase request.</p>';
        }

        // Signature Section
        $html .= '
            <div class="signature-section">
                <div class="signature-row">
                    <div class="signature-cell">
                        <div class="signature-label-and-name">
                            <span class="signature-label-text">Requested by:</span>
                            ' . htmlspecialchars($pr['requested_by']) . ' <!-- Directly embedding the name -->
                        </div>
                    </div>
                    <div class="signature-cell right-align">
                        <div class="signature-label-and-name">
                            <span class="signature-label-text">Approved by:</span>
                            ' . $approvedBy . ' <!-- Directly embedding the approved by data -->
                        </div>
                    </div>
                </div>
            </div>';

        $html .= '</div>'; // Close pr-container

        // Add a page break after each purchase request, except the last one
        if ($counter < count($purchaseRequests)) {
            $html .= '<div class="page-break"></div>';
        }
    }
} else {
    $html .= '<p style="text-align: center;">No purchase requests found for the selected criteria.</p>';
}

$html .= '
    <div class="footer">
        Report Generated on ' . date('Y-m-d H:i:s') . '
    </div>
</body>
</html>';

// --- 4. Instantiate Dompdf and Render PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Stream the generated PDF to the browser
$filename = "Detailed_Purchase_Requests_Report_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>
