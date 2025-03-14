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

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Handle image upload
if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $photo_name = '../uploads/' . basename($_FILES['photo']['name']);
    move_uploaded_file($photo_tmp, $photo_name);

    // Get image dimensions
    list($width, $height) = getimagesize($photo_name);
    $pdf->Image($photo_name, 10, 30, 40); // X=10, Y=30, Width=40

    // Calculate new Y-coordinate based on image height
    $newY = 30 + ($height * 40 / $width); // Height adjusted to width 40mm
    $pdf->SetY($newY + 10); // Add some padding below the image
} else {
    $photo_name = ''; // Default value if no image is uploaded
    $pdf->SetY(30); // Start content from Y=30 if no image is uploaded
}

// Add content to PDF
$pdf->Cell(40, 10, 'Full Name: ' . $_POST['full_name']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Contact Info: ' . $_POST['contact_info']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Bio: ' . $_POST['bio']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Soft Skills: ' . $_POST['soft_skills']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Technical Skills: ' . $_POST['technical_skills']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Academic Background: ' . $_POST['academic_background']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Work Experience: ' . $_POST['work_experience']);
$pdf->Ln();
$pdf->Cell(40, 10, 'Projects/Publications: ' . $_POST['projects_publications']);
$pdf->Ln();

// Save PDF
$pdf_file = '../uploads/portfolio_' . time() . '.pdf';
$pdf->Output('F', $pdf_file);

// Store data in database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'];
$contact_info = $_POST['contact_info'];
$bio = $_POST['bio'];
$soft_skills = $_POST['soft_skills'];
$technical_skills = $_POST['technical_skills'];
$academic_background = $_POST['academic_background'];
$work_experience = $_POST['work_experience'];
$projects_publications = $_POST['projects_publications'];

$sql = "INSERT INTO portfolio (user_id, full_name, contact_info, photo, bio, soft_skills, technical_skills, academic_background, work_experience, projects_publications, pdf_file, created_at) 
        VALUES ('$user_id', '$full_name', '$contact_info', '$photo_name', '$bio', '$soft_skills', '$technical_skills', '$academic_background', '$work_experience', '$projects_publications', '$pdf_file', NOW())";

if ($conn->query($sql) === TRUE) {
    echo "<div style='font-family: Arial, sans-serif; color: green; animation: fadeIn 3s; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;'>Portfolio saved successfully! <a href='$pdf_file' style='color: blue;'>Download PDF</a>.
    </div>
          <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            body {
                background: url('../gif/J59.gif') no-repeat center center fixed;
                background-size: cover;
            }
          </style>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>