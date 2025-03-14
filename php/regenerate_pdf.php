<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please <a href='../index.html'>login</a> first.");
}

require('../fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Portfolio', 0, 1, 'C');
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$portfolio_id = $_GET['id']; // Get the portfolio ID from the URL
$user_id = $_SESSION['user_id'];

// Fetch portfolio data from the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM portfolio WHERE id='$portfolio_id' AND user_id='$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Create a new PDF
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // Handle image
    if (!empty($row['photo'])) {
        $photo_name = $row['photo'];

        // Get image dimensions
        list($width, $height) = getimagesize($photo_name);
        $pdf->Image($photo_name, 10, 30, 40); // X=10, Y=30, Width=40

        // Calculate new Y-coordinate based on image height
        $newY = 30 + ($height * 40 / $width); // Height adjusted to width 40mm
        $pdf->SetY($newY + 10); // Add some padding below the image
    } else {
        $pdf->SetY(30); // Start content from Y=30 if no image is uploaded
    }

    // Add content to PDF
    $pdf->Cell(40, 10, 'Full Name: ' . $row['full_name']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Contact Info: ' . $row['contact_info']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Bio: ' . $row['bio']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Soft Skills: ' . $row['soft_skills']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Technical Skills: ' . $row['technical_skills']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Academic Background: ' . $row['academic_background']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Work Experience: ' . $row['work_experience']);
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Projects/Publications: ' . $row['projects_publications']);
    $pdf->Ln();

    // Save PDF
    $pdf_file = '../uploads/portfolio_' . time() . '.pdf';
    $pdf->Output('F', $pdf_file);

    echo "PDF regenerated successfully! <a href='$pdf_file'>Download PDF</a>.";
} else {
    echo "Portfolio not found.";
}

$conn->close();
?>