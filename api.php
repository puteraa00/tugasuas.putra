<?php
header("Content-Type: application/json");

$host = "localhost";
$user = "root"; // Ganti dengan username MySQL Anda
$password = ""; // Ganti dengan password MySQL Anda
$dbname = "perpustakaan"; // Ganti dengan nama database Anda

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$requestMethod = $_SERVER["REQUEST_METHOD"];
$path = explode("/", trim($_SERVER["PATH_INFO"], "/"));

$endpoint = $path[0];

switch ($endpoint) {
    case "kategori":
        handleKategori($conn, $requestMethod);
        break;
    case "buku":
        handleBuku($conn, $requestMethod);
        break;
    case "anggota":
        handleAnggota($conn, $requestMethod);
        break;
    case "peminjaman":
        handlePeminjaman($conn, $requestMethod);
        break;
    case "pengembalian":
        handlePengembalian($conn, $requestMethod);
        break;
    default:
        echo json_encode(["error" => "Endpoint not found"]);
        break;
}

$conn->close();

function handleKategori($conn, $method) {
    if ($method == "GET") {
        $result = $conn->query("SELECT * FROM Kategori");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } elseif ($method == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO Kategori (Nama_Kategori) VALUES (?)");
        $stmt->bind_param("s", $data['Nama_Kategori']);
        $stmt->execute();
        echo json_encode(["message" => "Kategori berhasil ditambahkan"]);
    } elseif ($method == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("DELETE FROM Kategori WHERE ID_Kategori = ?");
        $stmt->bind_param("i", $data['ID_Kategori']);
        $stmt->execute();
        echo json_encode(["message" => "Kategori berhasil dihapus"]);
    }
}

function handleBuku($conn, $method) {
    if ($method == "GET") {
        $result = $conn->query("SELECT * FROM Buku");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } elseif ($method == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO Buku (Judul, Penulis, Penerbit, Tahun_Terbit, ID_Kategori) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $data['Judul'], $data['Penulis'], $data['Penerbit'], $data['Tahun_Terbit'], $data['ID_Kategori']);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Buku berhasil ditambahkan"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error adding buku: " . $stmt->error]);
        }
    } elseif ($method == "DELETE") {
        // Ambil ID buku dari body request
        $data = json_decode(file_get_contents("php://input"), true);
        $idBuku = isset($data['ID_Buku']) ? intval($data['ID_Buku']) : null;
    
        if (!$idBuku) {
            http_response_code(400);
            echo json_encode(["message" => "ID_Buku is required"]);
            return;
        }
    
        // Lakukan penghapusan buku berdasarkan ID_Buku
        $stmt = $conn->prepare("DELETE FROM Buku WHERE ID_Buku = ?");
        $stmt->bind_param("i", $idBuku);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Buku berhasil dihapus"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting buku: " . $stmt->error]);
        }
    }
    
}

function handleAnggota($conn, $method) {
    if ($method == "GET") {
        $result = $conn->query("SELECT * FROM Anggota");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } elseif ($method == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO Anggota (Nama, Alamat, No_Telepon) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $data['Nama'], $data['Alamat'], $data['No_Telepon']);
        $stmt->execute();
        echo json_encode(["message" => "Anggota berhasil ditambahkan"]);
    } elseif ($method == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("DELETE FROM Anggota WHERE ID_Anggota = ?");
        $stmt->bind_param("i", $data['ID_Anggota']);
        $stmt->execute();
        echo json_encode(["message" => "Anggota berhasil dihapus"]);
    }
}

function handlePeminjaman($conn, $method) {
    if ($method == "GET") {
        $result = $conn->query("SELECT * FROM Peminjaman");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } elseif ($method == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        // Check if ID_Anggota exists
        $stmt = $conn->prepare("SELECT * FROM Anggota WHERE ID_Anggota = ?");
        $stmt->bind_param("i", $data['ID_Anggota']);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            echo json_encode(["error" => "ID_Anggota not found"]);
            return;
        }

        // Check if ID_Buku exists
        $stmt = $conn->prepare("SELECT * FROM Buku WHERE ID_Buku = ?");
        $stmt->bind_param("i", $data['ID_Buku']);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            echo json_encode(["error" => "ID_Buku not found"]);
            return;
        }

        // Insert Peminjaman
        $stmt = $conn->prepare("INSERT INTO Peminjaman (ID_Anggota, ID_Buku, Tanggal_Pinjam, Tanggal_Kembali, Status) VALUES (?, ?, ?, ?, ?)");
        $status = "Dipinjam";
        $stmt->bind_param("iisss", $data['ID_Anggota'], $data['ID_Buku'], $data['Tanggal_Pinjam'], $data['Tanggal_Kembali'], $status);
        $stmt->execute();
        echo json_encode(["message" => "Peminjaman berhasil dicatat"]);
    } elseif ($method == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("DELETE FROM Peminjaman WHERE ID_Peminjaman = ?");
        $stmt->bind_param("i", $data['ID_Peminjaman']);
        $stmt->execute();
        echo json_encode(["message" => "Peminjaman berhasil dihapus"]);
    }
}

function handlePengembalian($conn, $method) {
    if ($method == "GET") {
        $result = $conn->query("SELECT * FROM Pengembalian");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } elseif ($method == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO Pengembalian (ID_Peminjaman, Tanggal_Pengembalian, Denda) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $data['ID_Peminjaman'], $data['Tanggal_Pengembalian'], $data['Denda']);
        $stmt->execute();
        echo json_encode(["message" => "Pengembalian berhasil dicatat"]);
    } elseif ($method == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("DELETE FROM Pengembalian WHERE ID_Pengembalian = ?");
        $stmt->bind_param("i", $data['ID_Pengembalian']);
        $stmt->execute();
        echo json_encode(["message" => "Pengembalian berhasil dihapus"]);
    }
}
?>
