<?php
/**
 * Seed Master Catalog untuk Panglong ERP
 * Produk bangunan lengkap milik super admin (tenant_id = NULL)
 * Tenant baru bisa pilih produk dari sini untuk dijual
 */

$db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$now = date('Y-m-d H:i:s');

echo "=== SEED MASTER CATALOG PANGLONG (tenant_id = NULL) ===\n\n";

// === KATEGORI MASTER ===
echo "[1] Master Categories\n";
$categories = [
    'Semen & Beton', 'Bata & Hebel', 'Besi & Baja', 'Atap & Genteng',
    'Cat & Finishing', 'Keramik & Granit', 'Kayu & Plywood', 'Pipa & Plumbing',
    'Sanitary & Kamar Mandi', 'Listrik & Penerangan', 'Paku, Baut & Sekrup',
    'Alat Pertukangan', 'Pintu & Jendela', 'Kaca & Cermin', 'Gypsum & Plafon',
    'Lem & Perekat', 'Tali & Kawat', 'Safety Equipment', 'Lain-lain',
];
$catMap = [];
foreach ($categories as $cat) {
    $exists = $db->prepare("SELECT id FROM categories WHERE name = ? AND tenant_id IS NULL");
    $exists->execute([$cat]);
    $id = $exists->fetchColumn();
    if (!$id) {
        $db->prepare("INSERT INTO categories (name, is_active, tenant_id, created_at, updated_at) VALUES (?, 1, NULL, ?, ?)")
           ->execute([$cat, $now, $now]);
        $id = $db->lastInsertId();
        echo "  + $cat\n";
    } else {
        echo "  = $cat\n";
    }
    $catMap[$cat] = $id;
}

// === SATUAN MASTER ===
echo "\n[2] Master Unit Measurements\n";
$units = [
    ['pcs', 'Pieces'], ['box', 'Box'], ['dus', 'Dus'], ['zak', 'Zak'],
    ['kg', 'Kilogram'], ['m', 'Meter'], ['m2', 'Meter Persegi'], ['m3', 'Meter Kubik'],
    ['batang', 'Batang'], ['lembar', 'Lembar'], ['roll', 'Roll'], ['set', 'Set'],
    ['unit', 'Unit'], ['pack', 'Pack'], ['liter', 'Liter'], ['galon', 'Galon'],
    ['pail', 'Pail'], ['karung', 'Karung'], ['pasang', 'Pasang'], ['lot', 'Lot'],
];
foreach ($units as $u) {
    $exists = $db->prepare("SELECT id FROM unit_measurements WHERE code = ?");
    $exists->execute([$u[0]]);
    $existingId = $exists->fetchColumn();
    if (!$existingId) {
        $db->prepare("INSERT INTO unit_measurements (code, name, is_active, tenant_id, created_at, updated_at) VALUES (?, ?, 1, NULL, ?, ?)")
           ->execute([$u[0], $u[1], $now, $now]);
        echo "  + {$u[0]} ({$u[1]})\n";
    } else {
        // Update tenant_id to NULL if it was set to a tenant (make it master)
        echo "  = {$u[0]} (exists, id=$existingId)\n";
    }
}

// === PRODUK MASTER ===
echo "\n[3] Master Products\n";
$products = [
    // Semen & Beton
    ['Semen & Beton', 'Semen Gresik Portland 40kg', 'Semen Gresik', 'zak'],
    ['Semen & Beton', 'Semen Holcim Portland 40kg', 'Holcim', 'zak'],
    ['Semen & Beton', 'Semen Tiga Roda Portland 40kg', 'Tiga Roda', 'zak'],
    ['Semen & Beton', 'Semen Putih Gresik 40kg', 'Semen Gresik', 'zak'],
    ['Semen & Beton', 'Semen Mortar Instan 25kg', 'Sika', 'zak'],
    ['Semen & Beton', 'Beton Instan Ready Mix 40kg', 'Dynamix', 'zak'],
    ['Semen & Beton', 'Semen PCC Tiga Roda 40kg', 'Tiga Roda', 'zak'],
    ['Semen & Beton', 'Koral Split 5-10mm', 'Local', 'm3'],
    ['Semen & Beton', 'Pasir Beton', 'Local', 'm3'],
    ['Semen & Beton', 'Pasir Pasang', 'Local', 'm3'],

    // Bata & Hebel
    ['Bata & Hebel', 'Bata Merah', 'Local', 'pcs'],
    ['Bata & Hebel', 'Bata Ringan Hebel 60x20x10', 'Hebel', 'pcs'],
    ['Bata & Hebel', 'Bata Ringan Blesscon 60x20x10', 'Blesscon', 'pcs'],
    ['Bata & Hebel', 'Bata Ringan Grand Elephant 60x20x10', 'Grand Elephant', 'pcs'],
    ['Bata & Hebel', 'Bata Ringan Hoki 60x20x10', 'HOKI', 'pcs'],
    ['Bata & Hebel', 'Bata Ringan Citicon 60x20x10', 'Citicon', 'pcs'],
    ['Bata & Hebel', 'Bata Press', 'Local', 'pcs'],

    // Besi & Baja
    ['Besi & Baja', 'Besi Beton Polos 10mm x 12m', 'BJTD', 'batang'],
    ['Besi & Baja', 'Besi Beton Polos 8mm x 12m', 'BJTD', 'batang'],
    ['Besi & Baja', 'Besi Beton Polos 6mm x 12m', 'BJTD', 'batang'],
    ['Besi & Baja', 'Besi Beton Ulir 10mm x 12m', 'BJTS', 'batang'],
    ['Besi & Baja', 'Besi Beton Ulir 12mm x 12m', 'BJTS', 'batang'],
    ['Besi & Baja', 'Besi Beton Ulir 16mm x 12m', 'BJTS', 'batang'],
    ['Besi & Baja', 'Besi H-Beam 150x150 x 12m', 'KS', 'batang'],
    ['Besi & Baja', 'Besi IWF 150x75 x 12m', 'KS', 'batang'],
    ['Besi & Baja', 'Besi C-Channel 100x50 x 6m', 'KS', 'batang'],
    ['Besi & Baja', 'Besi Hollow 4x4 x 6m', 'BJYP', 'batang'],
    ['Besi & Baja', 'Besi Hollow 2x4 x 6m', 'BJYP', 'batang'],
    ['Besi & Baja', 'Baja Ringan 0.75mm x 6m', 'Truss', 'batang'],
    ['Besi & Baja', 'Baja Ringan 0.75mm x 7m', 'Truss', 'batang'],
    ['Besi & Baja', 'Wiremesh M8 210x420cm', 'SNI', 'lembar'],
    ['Besi & Baja', 'Wiremesh M10 210x420cm', 'SNI', 'lembar'],
    ['Besi & Baja', 'Kawat Bendrat 2mm 50m', 'Bendrat', 'roll'],

    // Atap & Genteng
    ['Atap & Genteng', 'Genteng Beton Garuda', 'Mutiara', 'pcs'],
    ['Atap & Genteng', 'Genteng Keramik Plentong', 'Plentong', 'pcs'],
    ['Atap & Genteng', 'Genteng Tanah Liat', 'Local', 'pcs'],
    ['Atap & Genteng', 'Spandek 0.4mm 1090mm x 3m', 'SNI', 'lembar'],
    ['Atap & Genteng', 'Spandek 0.4mm 1090mm x 4m', 'SNI', 'lembar'],
    ['Atap & Genteng', 'Spandek 0.3mm 1090mm x 3m', 'SNI', 'lembar'],
    ['Atap & Genteng', 'Bondek 0.4mm 1000mm x 3m', 'SNI', 'lembar'],
    ['Atap & Genteng', 'Talang Air PVC 6 inch 4m', 'Vinilon', 'batang'],
    ['Atap & Genteng', 'Talang Air PVC 4 inch 4m', 'Vinilon', 'batang'],
    ['Atap & Genteng', 'Nok Spandek 1090mm', 'SNI', 'pcs'],
    ['Atap & Genteng', 'Skrup Atap 12x40 1kg', 'Generic', 'kg'],

    // Cat & Finishing
    ['Cat & Finishing', 'Cat Tembok Dulux Vita 25kg', 'Dulux', 'galon'],
    ['Cat & Finishing', 'Cat Tembok Avian 25kg', 'Avian', 'galon'],
    ['Cat & Finishing', 'Cat Tembok Nippon Paint 25kg', 'Nippon', 'galon'],
    ['Cat & Finishing', 'Cat Tembok Catylac 25kg', 'Catylac', 'galon'],
    ['Cat & Finishing', 'Cat Kayu & Besi Avian 5kg', 'Avian', 'kg'],
    ['Cat & Finishing', 'Cat Kayu & Besi Nippon 5kg', 'Nippon', 'kg'],
    ['Cat & Finishing', 'Plamir Dulux 5kg', 'Dulux', 'pail'],
    ['Cat & Finishing', 'Plamir Nippon 5kg', 'Nippon', 'pail'],
    ['Cat & Finishing', 'Thinner A 5 Liter', 'Generic', 'liter'],
    ['Cat & Finishing', 'Thinner B 5 Liter', 'Generic', 'liter'],
    ['Cat & Finishing', 'Dempul Kayu 1kg', 'Avian', 'kg'],
    ['Cat & Finishing', 'Catskiller Alkali Killer 1kg', 'Dulux', 'kg'],
    ['Cat & Finishing', 'Kuas Cat 4 inch', 'Generic', 'pcs'],
    ['Cat & Finishing', 'Roller Cat 9 inch', 'Generic', 'pcs'],
    ['Cat & Finishing', 'Roller Cat 4 inch', 'Generic', 'pcs'],
    ['Cat & Finishing', 'Kuas Mini Roll 4 inch', 'Taiyo', 'pcs'],

    // Keramik & Granit
    ['Keramik & Granit', 'Keramik Lantai 40x40 Putih', 'Accura', 'box'],
    ['Keramik & Granit', 'Keramik Lantai 40x40 Grey', 'Accura', 'box'],
    ['Keramik & Granit', 'Keramik Dinding 25x40 Putih', 'Asia Tile', 'box'],
    ['Keramik & Granit', 'Keramik Dinding 25x40 Krem', 'Asia Tile', 'box'],
    ['Keramik & Granit', 'Granit 60x60 Polished', 'Infiniti', 'box'],
    ['Keramik & Granit', 'Granit 60x60 Matte', 'Infiniti', 'box'],
    ['Keramik & Granit', 'Granit Valentino 60x60', 'Valentino', 'box'],
    ['Keramik & Granit', 'Marmer Crema 60x60', 'Local', 'box'],
    ['Keramik & Granit', 'Nat Keramik Putih 1kg', 'Sika', 'kg'],
    ['Keramik & Granit', 'Lem Keramik Aplus 25kg', 'Aplus', 'zak'],

    // Kayu & Plywood
    ['Kayu & Plywood', 'Plywood 9mm 122x244cm', 'Local', 'lembar'],
    ['Kayu & Plywood', 'Plywood 12mm 122x244cm', 'Local', 'lembar'],
    ['Kayu & Plywood', 'Plywood 18mm 122x244cm', 'Local', 'lembar'],
    ['Kayu & Plywood', 'MDF 18mm 122x244cm', 'Local', 'lembar'],
    ['Kayu & Plywood', 'Blockboard 15mm 122x244cm', 'Local', 'lembar'],
    ['Kayu & Plywood', 'Kayu Balok 6x12 x 4m', 'Local', 'batang'],
    ['Kayu & Plywood', 'Kayu Papan 2x20 x 4m', 'Local', 'batang'],
    ['Kayu & Plywood', 'Kayu Jati 4x4 x 3m', 'Local', 'batang'],
    ['Kayu & Plywood', 'Lisplank Kayu 1x8 x 4m', 'Local', 'batang'],
    ['Kayu & Plywood', 'Papan Fibersemen EKA 122x244cm', 'EKA', 'lembar'],

    // Pipa & Plumbing
    ['Pipa & Plumbing', 'Pipa PVC AW 3 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC AW 4 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC AW 2 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC AW 1.5 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC AW 1 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC AW 0.5 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC D 3 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa PVC D 4 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa HDPE 2 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Pipa Conduit 0.5 inch 4m', 'Trilliun', 'batang'],
    ['Pipa & Plumbing', 'Elbow PVC 90 derajat 3 inch', 'Trilliun', 'pcs'],
    ['Pipa & Plumbing', 'Elbow PVC 90 derajat 2 inch', 'Trilliun', 'pcs'],
    ['Pipa & Plumbing', 'Tee PVC 3 inch', 'Trilliun', 'pcs'],
    ['Pipa & Plumbing', 'Lem PVC 1kg', 'Trilliun', 'kg'],
    ['Pipa & Plumbing', 'Selang Air 1/2 inch 50m', 'Vinilon', 'roll'],
    ['Pipa & Plumbing', 'Selang Gas 1/2 inch 10m', 'Vinilon', 'roll'],

    // Sanitary & Kamar Mandi
    ['Sanitary & Kamar Mandi', 'Closet Duduk Toto', 'TOTO', 'unit'],
    ['Sanitary & Kamar Mandi', 'Closet Jongkok Toto', 'TOTO', 'unit'],
    ['Sanitary & Kamar Mandi', 'Washtafel TOTO Lavatory', 'TOTO', 'unit'],
    ['Sanitary & Kamar Mandi', 'Washtafel American Standard', 'American Standard', 'unit'],
    ['Sanitary & Kamar Mandi', 'Kran Air TOTO 1/2 inch', 'TOTO', 'pcs'],
    ['Sanitary & Kamar Mandi', 'Kran Air TOTO 3/4 inch', 'TOTO', 'pcs'],
    ['Sanitary & Kamar Mandi', 'Shower TOTO', 'TOTO', 'set'],
    ['Sanitary & Kamar Mandi', 'Urinoir TOTO', 'TOTO', 'unit'],
    ['Sanitary & Kamar Mandi', 'Floor Drain Stainless 4 inch', 'Generic', 'pcs'],
    ['Sanitary & Kamar Mandi', 'Pintu Kamar Mandi PVC', 'Platinum', 'unit'],
    ['Sanitary & Kamar Mandi', 'Pintu Kamar Mandi Aluminium', 'Galvalum', 'unit'],
    ['Sanitary & Kamar Mandi', 'Tandon Air 1000L', 'Profil Tank', 'unit'],
    ['Sanitary & Kamar Mandi', 'Tandon Air Stainless 500L', 'Profil Tank', 'unit'],

    // Listrik & Penerangan
    ['Listrik & Penerangan', 'Kabel NYM 3x2.5mm 50m', 'Supreme', 'roll'],
    ['Listrik & Penerangan', 'Kabel NYM 3x1.5mm 50m', 'Supreme', 'roll'],
    ['Listrik & Penerangan', 'Kabel NYM 2x2.5mm 50m', 'Supreme', 'roll'],
    ['Listrik & Penerangan', 'Kabel NYM 2x1.5mm 50m', 'Supreme', 'roll'],
    ['Listrik & Penerangan', 'Kabel NYY 4x2.5mm 50m', 'Supreme', 'roll'],
    ['Listrik & Penerangan', 'Saklar Single', 'Panasonic', 'pcs'],
    ['Listrik & Penerangan', 'Saklar Tunggal', 'Panasonic', 'pcs'],
    ['Listrik & Penerangan', 'Stop Kontak', 'Panasonic', 'pcs'],
    ['Listrik & Penerangan', 'MCB 6A', 'Schneider', 'pcs'],
    ['Listrik & Penerangan', 'MCB 10A', 'Schneider', 'pcs'],
    ['Listrik & Penerangan', 'MCB 16A', 'Schneider', 'pcs'],
    ['Listrik & Penerangan', 'Fitting Broco', 'Broco', 'pcs'],
    ['Listrik & Penerangan', 'Lampu LED 12W', 'Philips', 'pcs'],
    ['Listrik & Penerangan', 'Lampu LED 18W', 'Philips', 'pcs'],
    ['Listrik & Penerangan', 'Lampu LED 24W', 'Philips', 'pcs'],
    ['Listrik & Penerangan', 'Lampu TL 4ft', 'Philips', 'pcs'],

    // Paku, Baut & Sekrup
    ['Paku, Baut & Sekrup', 'Paku Beton 2 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Paku Beton 3 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Paku Beton 4 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Paku Kayu 2 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Paku Kayu 3 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Paku Payung 1 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Baut Mur 10mm x 50mm 1set', 'Generic', 'set'],
    ['Paku, Baut & Sekrup', 'Baut Mur 12mm x 50mm 1set', 'Generic', 'set'],
    ['Paku, Baut & Sekrup', 'Sekrup Galvalum 12x40 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Sekrup Kayu 3 inch 1kg', 'Generic', 'kg'],
    ['Paku, Baut & Sekrup', 'Dynabolt 10mm x 100mm 1set', 'Generic', 'set'],
    ['Paku, Baut & Sekrup', 'Dynabolt 12mm x 100mm 1set', 'Generic', 'set'],

    // Alat Pertukangan
    ['Alat Pertukangan', 'Palu Nailer 500gr', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Palu Geofu 1000gr', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Gergaji Kayu', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Gergaji Besi', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Meteran 5m', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Obeng Set', 'Generic', 'set'],
    ['Alat Pertukangan', 'Bor Listrik 13mm', 'Bosch', 'unit'],
    ['Alat Pertukangan', 'Gerinda 4 inch', 'Bosch', 'unit'],
    ['Alat Pertukangan', 'Mata Potong Keramik 4 inch', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Mata Potong Turbo 4 inch', 'Generic', 'pcs'],
    ['Alat Pertukangan', 'Amplas Kayu 1 pack', 'Generic', 'pack'],
    ['Alat Pertukangan', 'Kuas Tembok 4 inch', 'Generic', 'pcs'],

    // Pintu & Jendela
    ['Pintu & Jendela', 'Pintu Aluminium Sliding 80x210', 'Galvalum', 'unit'],
    ['Pintu & Jendela', 'Pintu Aluminium Sliding 100x210', 'Galvalum', 'unit'],
    ['Pintu & Jendela', 'Jendela Aluminium 60x120', 'Galvalum', 'unit'],
    ['Pintu & Jendela', 'Jendela Aluminium 80x120', 'Galvalum', 'unit'],
    ['Pintu & Jendela', 'Engsel Pintu Stainless 4 inch', 'Generic', 'pasang'],
    ['Pintu & Jendela', 'Handle Pintu Stainless', 'Generic', 'set'],
    ['Pintu & Jendela', 'Kunci Pintu Handle', 'Yale', 'set'],
    ['Pintu & Jendela', 'Kunci Pintu Silinder', 'Yale', 'set'],
    ['Pintu & Jendela', 'Gembok 40mm', 'Yale', 'pcs'],
    ['Pintu & Jendela', 'Gembok 50mm', 'Yale', 'pcs'],

    // Kaca & Cermin
    ['Kaca & Cermin', 'Kaca Bening 5mm 120x240cm', 'Mulia', 'lembar'],
    ['Kaca & Cermin', 'Kaca Bening 6mm 120x240cm', 'Mulia', 'lembar'],
    ['Kaca & Cermin', 'Kaca Tempered 8mm', 'Mulia', 'm2'],
    ['Kaca & Cermin', 'Cermin Silver 5mm 120x240cm', 'Mulia', 'lembar'],
    ['Kaca & Cermin', 'Glass Block Mulia Tipe Parang', 'Mulia', 'pcs'],

    // Gypsum & Plafon
    ['Gypsum & Plafon', 'Plafon Gypsum 9mm 122x244cm', 'Knauf', 'lembar'],
    ['Gypsum & Plafon', 'Plafon Gypsum 12.5mm 122x244cm', 'Knauf', 'lembar'],
    ['Gypsum & Plafon', 'Rangka Plafon Hollow Galvanis 4x2 x 3m', 'Generic', 'batang'],
    ['Gypsum & Plafon', 'Rangka Plafon Hollow Galvanis 4x2 x 4m', 'Generic', 'batang'],
    ['Gypsum & Plafon', 'Clip Adjuster Hanger Knauf', 'Knauf', 'pcs'],
    ['Gypsum & Plafon', 'Joint Compound Kalsi 25kg', 'Kalsi', 'pail'],
    ['Gypsum & Plafon', 'Screws Gypsum 25mm 1kg', 'Generic', 'kg'],
    ['Gypsum & Plafon', 'Plywood Plank Jati', 'Conwood', 'batang'],

    // Lem & Perekat
    ['Lem & Perekat', 'Lem Silikon Taiyo Pak Tukang', 'Taiyo', 'pcs'],
    ['Lem & Perekat', 'Lem PVC Trilliun 1kg', 'Trilliun', 'kg'],
    ['Lem & Perekat', 'Lem Kayu Fox 1kg', 'Fox', 'kg'],
    ['Lem & Perekat', 'Lem Tembak Aica 500gr', 'Aica', 'pcs'],
    ['Lem & Perekat', 'Double Tip 12mm x 10m', 'Generic', 'roll'],
    ['Lem & Perekat', 'Lakban Kertas 50mm x 20m', 'Taiyo', 'roll'],

    // Tali & Kawat
    ['Tali & Kawat', 'Tali Tambang 6mm x 50m', 'Generic', 'roll'],
    ['Tali & Kawat', 'Tali Tambang 8mm x 50m', 'Generic', 'roll'],
    ['Tali & Kawat', 'Tali Rafia 500gr', 'Generic', 'pcs'],
    ['Tali & Kawat', 'Kawat Bendrat 2mm x 50m', 'Bendrat', 'roll'],
    ['Tali & Kawat', 'Kawat Galvanis 1.2mm x 50m', 'Generic', 'roll'],
    ['Tali & Kawat', 'Kawat Duri 500mm x 50m', 'Generic', 'roll'],

    // Safety Equipment
    ['Safety Equipment', 'Helm Safety Proyek', 'SNI', 'pcs'],
    ['Safety Equipment', 'Sabuk Pengaman Body Harness', 'SNI', 'set'],
    ['Safety Equipment', 'Sepatu Safety Boots', 'SNI', 'pasang'],
    ['Safety Equipment', 'Sarung Tangan Kerja', 'Generic', 'pasang'],
    ['Safety Equipment', 'Kacamata Safety', 'SNI', 'pcs'],
    ['Safety Equipment', 'Masker Dust 1 box', '3M', 'box'],
    ['Safety Equipment', 'Ear Plug', '3M', 'pasang'],

    // Lain-lain
    ['Lain-lain', 'Waterproofing Sika 4kg', 'Sika', 'pail'],
    ['Lain-lain', 'Waterproofing Fosroc Nitoproof 20kg', 'Fosroc', 'pail'],
    ['Lain-lain', 'Aluminium Foil Aplus', 'Aplus', 'roll'],
    ['Lain-lain', 'Lis Aluminium 1x2 x 4m', 'Generic', 'batang'],
    ['Lain-lain', 'Lis Kayu 1x3 x 4m', 'Generic', 'batang'],
    ['Lain-lain', 'Roskam', 'Generic', 'pcs'],
    ['Lain-lain', 'Blencong', 'Generic', 'pcs'],
    ['Lain-lain', 'Tangga Aluminium 6 step', 'Generic', 'unit'],
    ['Lain-lain', 'Tangga Putar', 'Generic', 'unit'],
    ['Lain-lain', 'Gerobak Dorong', 'Generic', 'unit'],
];

$count = 0;
foreach ($products as $p) {
    $catName = $p[0];
    $name = $p[1];
    $brand = $p[2];
    $unitCode = $p[3];
    $catId = $catMap[$catName] ?? null;
    $unitId = $db->query("SELECT id FROM unit_measurements WHERE code = " . $db->quote($unitCode))->fetchColumn();

    // Generate code: KATEGORI-INDEX
    $prefix = strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($catName)), 0, 3));
    $code = 'MST-' . $prefix . '-' . str_pad(++$count, 4, '0', STR_PAD_LEFT);

    // Check if product already exists (by name, any tenant)
    $exists = $db->prepare("SELECT id, tenant_id FROM products WHERE name = ?");
    $exists->execute([$name]);
    $existing = $exists->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        $db->prepare("INSERT INTO products (code, name, category_id, brand, min_stock, max_stock, buy_price, sell_price, is_active, created_at, updated_at, tenant_id) VALUES (?, ?, ?, ?, 0, 0, 0, 0, 1, ?, ?, NULL)")
           ->execute([$code, $name, $catId, $brand, $now, $now]);
        $pid = $db->lastInsertId();

        // Add base unit
        if ($unitId) {
            $db->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, is_base_unit, price_per_unit, created_at, updated_at, tenant_id) VALUES (?, ?, 1, 1, 0, ?, ?, NULL)")
               ->execute([$pid, $unitCode, $now, $now]);
        }

        echo "  + $code | $name | $brand | $catName\n";
    } else {
        // If product exists but belongs to a tenant, upgrade it to master (tenant_id = NULL)
        if ($existing['tenant_id'] !== null) {
            // Skip - don't modify tenant products
            echo "  = $name (tenant product, skip)\n";
        } else {
            echo "  = $name (already master)\n";
        }
    }
}

// === SUMMARY ===
echo "\n=== SUMMARY ===\n";
echo "Master categories: " . $db->query("SELECT COUNT(*) FROM categories WHERE tenant_id IS NULL")->fetchColumn() . "\n";
echo "Master units: " . $db->query("SELECT COUNT(*) FROM unit_measurements WHERE tenant_id IS NULL")->fetchColumn() . "\n";
echo "Master products: " . $db->query("SELECT COUNT(*) FROM products WHERE tenant_id IS NULL")->fetchColumn() . "\n";
echo "\nDone.\n";
