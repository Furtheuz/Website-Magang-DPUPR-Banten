<?php
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

include "config/db.php";

$id = (int) $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, u.nama, u.email 
    FROM peserta p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = $id
"));

$html = '
<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
    .idcard {
        width: 100%;
        height: 100%;
        border: 2px solid #000;
        padding: 10px 20px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    h3 {
        margin: 0 0 10px;
        text-align: center;
        font-size: 16px;
        border-bottom: 1px solid #333;
        padding-bottom: 5px;
    }
    p {
        margin: 4px 0;
        font-size: 12px;
    }
    small {
        display: block;
        text-align: right;
        font-size: 10px;
        margin-top: 10px;
    }
</style>

<div class="idcard">
    <h3>ID CARD MAGANG</h3>
    <p><strong>Nama:</strong> ' . $data['nama'] . '</p>
    <p><strong>Email:</strong> ' . $data['email'] . '</p>
    <p><strong>Institusi ID:</strong> ' . $data['institusi_id'] . '</p>
    <small>Dicetak pada ' . date('d-m-Y') . '</small>
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 242.65, 153.00]); // ukuran ID Card (mm to pt)

$dompdf->render();
$dompdf->stream("idcard_{$data['nama']}.pdf", ["Attachment" => false]);
