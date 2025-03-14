<?php
declare(strict_types=1);

// Session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Authentication required']));
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Define base directories
define('BASE_DIR', __DIR__ . '/../');
define('UPLOAD_DIR', BASE_DIR . 'uploads/');
define('PDF_DIR', BASE_DIR . 'pdfs/');

try {
    // Validate and process input
    $portfolioData = validateAndProcessInput();
    
    // Generate PDF
    $pdfPath = generatePDF($portfolioData);
    
    // Save to database
    saveToDatabase($portfolioData, $pdfPath);
    
    echo json_encode([
        'success' => true,
        'pdf' => basename($pdfPath)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("SYSTEM ERROR: " . $e->getMessage());
    die(json_encode([
        'error' => 'Internal server error',
        'details' => $e->getMessage()
    ]));
}

function validateAndProcessInput(): array {
    // Validate required fields
    $required = [
        'full_name' => [
            'pattern' => '/^[A-Za-z ]{2,50}$/',
            'error' => 'Invalid name format'
        ],
        'contact_info' => [
            'pattern' => '/^[+0-9 ]{10,15}$/',
            'error' => 'Invalid contact format'
        ],
        'bio' => [
            'min' => 10,
            'max' => 500
        ]
    ];

    $errors = [];
    foreach ($required as $field => $rules) {
        $value = trim($_POST[$field] ?? '');
        
        // Validate presence
        if (empty($value)) {
            $errors[$field] = "Field is required";
            continue;
        }
        
        // Validate pattern
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
            $errors[$field] = $rules['error'];
        }
        
        // Validate length
        if (isset($rules['min']) && strlen($value) < $rules['min']) {
            $errors[$field] = "Minimum {$rules['min']} characters required";
        }
        
        if (isset($rules['max']) && strlen($value) > $rules['max']) {
            $errors[$field] = "Maximum {$rules['max']} characters allowed";
        }
    }

    // Validate file upload
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors['photo'] = 'Valid photo required';
    }

    if (!empty($errors)) {
        http_response_code(400);
        die(json_encode(['errors' => $errors]));
    }

    // Process file upload
    $photoPath = processUploadedFile();

    return [
        'user_id' => (int)$_SESSION['user_id'],
        'full_name' => htmlspecialchars($_POST['full_name']),
        'contact_info' => htmlspecialchars($_POST['contact_info']),
        'bio' => htmlspecialchars($_POST['bio']),
        'skills' => $_POST['skills'] ?? [],
        'academic' => $_POST['academic'] ?? [],
        'work' => $_POST['work'] ?? [],
        'projects' => $_POST['projects'] ?? [],
        'photo' => $photoPath
    ];
}

function processUploadedFile(): string {
    // Create upload directory
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        throw new Exception('Invalid file type. Only JPG/PNG allowed');
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
    $targetPath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
        throw new Exception('File upload failed. Check permissions.');
    }

    return $targetPath;
}

function generatePDF(array $data): string {
    // Create PDF directory
    if (!file_exists(PDF_DIR)) {
        if (!mkdir(PDF_DIR, 0755, true)) {
            throw new Exception('Failed to create PDF directory');
        }
    }

    require_once(BASE_DIR . 'fpdf/fpdf.php');
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Add content
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Portfolio', 0, 1, 'C');
    
    // Personal Info
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Name: ' . $data['full_name'], 0, 1);
    $pdf->Cell(0, 10, 'Contact: ' . $data['contact_info'], 0, 1);
    $pdf->MultiCell(0, 10, 'Bio: ' . $data['bio']);
    
    // Skills
    if (!empty($data['skills'])) {
        $pdf->Cell(0, 10, 'Skills:', 0, 1);
        foreach ($data['skills'] as $skill) {
            $name = htmlspecialchars($skill['name'] ?? 'Unnamed Skill');
            $type = htmlspecialchars($skill['type'] ?? 'Unknown Type');
            $pdf->Cell(0, 10, "- {$name} ({$type})", 0, 1);
        }
    }
    
    // Academic
    if (!empty($data['academic'])) {
        $pdf->Cell(0, 10, 'Academic Background:', 0, 1);
        foreach ($data['academic'] as $entry) {
            $text = sprintf("%s - %s (%s to %s)",
                htmlspecialchars($entry['institution'] ?? ''),
                htmlspecialchars($entry['degree'] ?? ''),
                htmlspecialchars($entry['start'] ?? ''),
                htmlspecialchars($entry['end'] ?? '')
            );
            $pdf->MultiCell(0, 10, $text);
        }
    }
    
    // Work Experience
    if (!empty($data['work'])) {
        $pdf->Cell(0, 10, 'Work Experience:', 0, 1);
        foreach ($data['work'] as $entry) {
            $text = sprintf("%s at %s (%s to %s)",
                htmlspecialchars($entry['position'] ?? ''),
                htmlspecialchars($entry['company'] ?? ''),
                htmlspecialchars($entry['start'] ?? ''),
                htmlspecialchars($entry['end'] ?? '')
            );
            $pdf->MultiCell(0, 10, $text);
        }
    }
    
    // Projects
    if (!empty($data['projects'])) {
        $pdf->Cell(0, 10, 'Projects:', 0, 1);
        foreach ($data['projects'] as $entry) {
            $text = sprintf("%s: %s",
                htmlspecialchars($entry['title'] ?? ''),
                htmlspecialchars($entry['description'] ?? '')
            );
            if (!empty($entry['link'])) {
                $text .= "\nLink: " . htmlspecialchars($entry['link']);
            }
            $pdf->MultiCell(0, 10, $text);
        }
    }
    
    // Add photo
    if (file_exists($data['photo'])) {
        $pdf->Image($data['photo'], 10, $pdf->GetY(), 50);
    }
    
    // Save PDF
    $filename = PDF_DIR . 'portfolio_' . uniqid() . '.pdf';
    $pdf->Output('F', $filename);
    
    if (!file_exists($filename)) {
        throw new Exception('PDF generation failed');
    }
    
    return $filename;
}

function saveToDatabase(array $data, string $pdfPath): void {
    $db = new mysqli("localhost", "root", "", "portfolio_db");
    if ($db->connect_errno) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    $sql = "INSERT INTO portfolio (
        user_id, full_name, contact_info, photo, bio,
        skills, academic, work_experience, projects, pdf_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    // Create variables for JSON encoded values
    $skills = json_encode($data['skills']);
    $academic = json_encode($data['academic']);
    $work = json_encode($data['work']);
    $projects = json_encode($data['projects']);

    $stmt->bind_param("isssssssss",
        $data['user_id'],
        $data['full_name'],
        $data['contact_info'],
        $data['photo'],
        $data['bio'],
        $skills,    // Now using variable instead of direct json_encode()
        $academic,  // Same here
        $work,      // And here
        $projects,  // And here
        $pdfPath
    );

    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }

    $stmt->close();
    $db->close();





    // After successful PDF generation
$pdfUrl = '/pdfs/' . basename($pdfPath);
echo json_encode([
    'success' => true,
    'pdf' => basename($pdfPath),
    'download_url' => $pdfUrl
]);
}