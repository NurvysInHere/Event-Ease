<?php
// dashboard/dashboard_overview.php

// JANGAN include db.php lagi di sini jika sudah diinclude di admin.php
// Cek apakah koneksi sudah tersedia
if (!isset($conn)) {
    // Jika belum, include db.php sekali saja
    require_once '../config/db.php';
}

// --- Ambil Data Statistik ---
try {
    // 1. Total Akun Eskul Terdaftar (role 'user')
    $query_total_eskul = "SELECT COUNT(id) AS total_eskul FROM users WHERE role = 'user'";
    $stmt_total_eskul = $conn->prepare($query_total_eskul);
    $stmt_total_eskul->execute();
    $total_eskul = $stmt_total_eskul->fetch(PDO::FETCH_ASSOC)['total_eskul'];

    // 2. Total Pengajuan Event
    $query_total_pengajuan = "SELECT COUNT(id) AS total_pengajuan FROM event_pengajuan";
    $stmt_total_pengajuan = $conn->prepare($query_total_pengajuan);
    $stmt_total_pengajuan->execute();
    $total_pengajuan = $stmt_total_pengajuan->fetch(PDO::FETCH_ASSOC)['total_pengajuan'];

    // 3. Penerimaan (Accepted) Pengajuan Event
    $query_accepted_pengajuan = "SELECT COUNT(id) AS accepted_pengajuan FROM event_pengajuan WHERE status = 'accepted'";
    $stmt_accepted_pengajuan = $conn->prepare($query_accepted_pengajuan);
    $stmt_accepted_pengajuan->execute();
    $accepted_pengajuan = $stmt_accepted_pengajuan->fetch(PDO::FETCH_ASSOC)['accepted_pengajuan'];

    // 4. Penolakan (Rejected) Pengajuan Event
    $query_rejected_pengajuan = "SELECT COUNT(id) AS rejected_pengajuan FROM event_pengajuan WHERE status = 'rejected'";
    $stmt_rejected_pengajuan = $conn->prepare($query_rejected_pengajuan);
    $stmt_rejected_pengajuan->execute();
    $rejected_pengajuan = $stmt_rejected_pengajuan->fetch(PDO::FETCH_ASSOC)['rejected_pengajuan'];

    // 5. Pending Pengajuan Event
    $query_pending_pengajuan = "SELECT COUNT(id) AS pending_pengajuan FROM event_pengajuan WHERE status = 'pending'";
    $stmt_pending_pengajuan = $conn->prepare($query_pending_pengajuan);
    $stmt_pending_pengajuan->execute();
    $pending_pengajuan = $stmt_pending_pengajuan->fetch(PDO::FETCH_ASSOC)['pending_pengajuan'];

    // 6. Total Budget dari Semua Pengajuan (Accepted)
    $query_total_budget_accepted = "SELECT SUM(budget) AS total_budget FROM event_pengajuan WHERE status = 'accepted'";
    $stmt_total_budget_accepted = $conn->prepare($query_total_budget_accepted);
    $stmt_total_budget_accepted->execute();
    $total_budget_accepted = $stmt_total_budget_accepted->fetch(PDO::FETCH_ASSOC)['total_budget'] ?: 0;

    // 7. Total Budget per Eskul
    $query_budget_per_eskul = "SELECT pengaju, SUM(budget) AS total_budget_eskul FROM event_pengajuan WHERE status = 'accepted' GROUP BY pengaju ORDER BY total_budget_eskul DESC";
    $stmt_budget_per_eskul = $conn->prepare($query_budget_per_eskul);
    $stmt_budget_per_eskul->execute();
    $budget_per_eskul = $stmt_budget_per_eskul->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error mengambil statistik: " . $e->getMessage());
}
?>

<div class="content-area">
    <h2><i class="fas fa-chart-line"></i> Dashboard Admin</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Total Akun Eskul</h3>
            <p><?= $total_eskul ?> Terdaftar</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <h3>Total Pengajuan</h3>
            <p><?= $total_pengajuan ?> Pengajuan</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Diterima</h3>
            <p><?= $accepted_pengajuan ?> Diterima</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h3>Ditolak</h3>
            <p><?= $rejected_pengajuan ?> Ditolak</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3>Pending</h3>
            <p><?= $pending_pengajuan ?> Pending</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <h3>Total Budget Disetujui</h3>
            <p>Rp <?= number_format($total_budget_accepted, 0, ',', '.') ?></p>
        </div>
    </div>

    <h3><i class="fas fa-chart-pie"></i> Budget Disetujui per Eskul</h3>
    <?php if (count($budget_per_eskul) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama Eskul (Pengaju)</th>
                        <th>Total Budget Disetujui</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budget_per_eskul as $eskul) : ?>
                        <tr>
                            <td><i class="fas fa-user-circle"></i> <?= htmlspecialchars($eskul['pengaju']) ?></td>
                            <td><strong>Rp <?= number_format($eskul['total_budget_eskul'], 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-pie" style="font-size: 3em; opacity: 0.5;"></i>
            <p>Belum ada budget yang disetujui untuk setiap eskul.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 2.5em;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.stat-card h3 {
    margin-top: 0;
    font-size: 1.1em;
    color: var(--text-color);
    margin-bottom: 10px;
    font-weight: 600;
}

.stat-card p {
    font-size: 2em;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0;
}

/* Warna berbeda untuk setiap card */
.stat-card:nth-child(1):before { background: var(--gradient-primary); }
.stat-card:nth-child(2):before { background: var(--gradient-accent); }
.stat-card:nth-child(3):before { background: var(--gradient-success); }
.stat-card:nth-child(4):before { background: var(--gradient-danger); }
.stat-card:nth-child(5):before { background: var(--gradient-warning); }
.stat-card:nth-child(6):before { background: var(--gradient-secondary); }

.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border-radius: 8px;
    overflow: hidden;
}

table th, table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

table th {
    background-color: var(--bg-color);
    font-weight: 600;
    color: var(--text-color);
}

table tbody tr:hover {
    background-color: var(--bg-color);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
    background-color: var(--bg-color);
    border-radius: 10px;
    margin-top: 20px;
}
</style>