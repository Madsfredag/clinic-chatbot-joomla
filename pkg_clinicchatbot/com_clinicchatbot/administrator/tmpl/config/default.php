<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$data = $this->paramsData;
$isRegistered = !empty($data['is_registered']);
?>

<style>
    .clinicchatbot-admin {
        max-width: 1120px;
        padding-top: 0.5rem;
    }

    .clinicchatbot-admin__hero {
        margin-bottom: 1.5rem;
    }

    .clinicchatbot-admin__title {
        margin: 0 0 0.35rem;
        font-size: 1.9rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #97a0b1;
    }

    .clinicchatbot-admin__subtitle {
        margin: 0;
        max-width: 760px;
        color: #97a0b1;
        font-size: 0.98rem;
        line-height: 1.6;
    }

    .clinicchatbot-admin .card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        background: #ffffff;
    }

    .clinicchatbot-admin .card-header {
        background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.25rem;
    }

    .clinicchatbot-admin .card-header h2 {
        color: #111827;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .clinicchatbot-admin .card-body {
        padding: 1.25rem;
        background: #ffffff;
    }

    .clinicchatbot-admin .card-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb !important;
    }

    .clinicchatbot-admin .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.45rem;
    }

    .clinicchatbot-admin .form-control,
    .clinicchatbot-admin textarea,
    .clinicchatbot-admin input[type="text"],
    .clinicchatbot-admin input[type="url"],
    .clinicchatbot-admin input[type="number"],
    .clinicchatbot-admin input[type="file"] {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        min-height: 48px;
        padding: 0.78rem 0.95rem;
        background: #ffffff;
        color: #111827;
        box-shadow: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .clinicchatbot-admin textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .clinicchatbot-admin .form-control:focus,
    .clinicchatbot-admin textarea:focus,
    .clinicchatbot-admin input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
        background: #ffffff;
        color: #111827;
    }

    .clinicchatbot-admin .form-control.bg-light {
        background: #f8fafc !important;
        border-color: #dbe5f0;
        color: #374151;
        display: flex;
        align-items: center;
    }

    .clinicchatbot-admin .form-text,
    .clinicchatbot-admin .text-muted {
        color: #6b7280 !important;
    }

    .clinicchatbot-admin .badge {
        border-radius: 999px;
        padding: 0.48rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .clinicchatbot-admin .bg-success {
        background-color: #ecfdf3 !important;
        color: #047857 !important;
        border: 1px solid #a7f3d0;
    }

    .clinicchatbot-admin .bg-secondary {
        background-color: #f3f4f6 !important;
        color: #292a2c !important;
        border: 1px solid #e5e7eb;
    }

    .clinicchatbot-admin .btn {
        border-radius: 12px;
        padding: 0.72rem 1rem;
        font-weight: 600;
        min-height: 44px;
        color: black;
    }

    .clinicchatbot-admin .btn-primary {
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.16);
    }

    .clinicchatbot-admin .btn-outline-primary,
    .clinicchatbot-admin .btn-outline-secondary {
        background: #ffffff;
    }

    .clinicchatbot-admin .alert {
        border-radius: 14px;
        border: 1px solid transparent;
    }

    .clinicchatbot-admin .alert-warning {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }

    .clinicchatbot-admin code {
        padding: 0.18rem 0.38rem;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.88em;
    }

    @media (max-width: 991px) {
        .clinicchatbot-admin__title {
            font-size: 1.65rem;
        }

        .clinicchatbot-admin .card-header,
        .clinicchatbot-admin .card-body,
        .clinicchatbot-admin .card-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>


<div class="container-fluid clinicchatbot-admin">
    <div class="clinicchatbot-admin__hero">
        <h1 class="clinicchatbot-admin__title">Chatbot helper</h1>
        <p class="clinicchatbot-admin__subtitle">
            Udfyld klinikkens oplysninger og klik på <strong>Registrer klinik</strong>. Resten oprettes
            automatisk.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <form action="index.php?option=com_clinicchatbot&task=config.save" method="post">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Klinikoplysninger</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="clinic_name" class="form-label">Kliniknavn</label>
                                <input type="text" class="form-control" id="clinic_name" name="jform[clinic_name]"
                                    value="<?= htmlspecialchars($data['clinic_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="clinic_phone" class="form-label">Telefonnummer</label>
                                <input type="text" class="form-control" id="clinic_phone" name="jform[clinic_phone]"
                                    value="<?= htmlspecialchars($data['clinic_phone'], ENT_QUOTES, 'UTF-8') ?>"
                                    required>
                            </div>

                            <div class="col-12">
                                <label for="clinic_address" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="clinic_address" name="jform[clinic_address]"
                                    value="<?= htmlspecialchars($data['clinic_address'], ENT_QUOTES, 'UTF-8') ?>"
                                    required>
                            </div>

                            <div class="col-12">
                                <label for="booking_url" class="form-label">Booking URL</label>
                                <input type="url" class="form-control" id="booking_url" name="jform[booking_url]"
                                    value="<?= htmlspecialchars($data['booking_url'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="col-12">
                                <label for="welcome_message" class="form-label">Velkomstbesked</label>
                                <textarea id="welcome_message" name="jform[welcome_message]" class="form-control"
                                    rows="4"
                                    required><?= htmlspecialchars($data['welcome_message'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="col-12">
                                <label for="clinic_knowledge" class="form-label">Klinikviden</label>
                                <textarea id="clinic_knowledge" name="jform[clinic_knowledge]" class="form-control"
                                    rows="10"><?= htmlspecialchars($data['clinic_knowledge'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="form-text">
                                    Brug dette felt til særlige behandlinger, parkeringsinfo, åbningstider, nødinfo og
                                    anden klinikspecifik viden.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Forbindelse</h2>
                        <?php if ($isRegistered): ?>
                            <span class="badge bg-success">Forbundet</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Ikke registreret</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">

                            <div class="col-12">
                                <label class="form-label">Forbindelsesstatus</label>
                                <div class="form-control bg-light">
                                    <?php if ($isRegistered): ?>
                                        Client ID:
                                        <code
                                            class="ms-2"><?= htmlspecialchars($data['client_id'], ENT_QUOTES, 'UTF-8') ?></code>
                                    <?php else: ?>
                                        <p>
                                            Ingen credentials endnu. Klik på <strong>Registrer klinik</strong> nedenfor.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Gem ændringer</button>
                        <button type="submit" formaction="index.php?option=com_clinicchatbot&task=config.registerClinic"
                            class="btn btn-outline-primary">
                            <?= $isRegistered ? 'Registrer igen' : 'Registrer klinik' ?>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="jform[client_id]"
                    value="<?= htmlspecialchars($data['client_id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="jform[client_secret]"
                    value="<?= htmlspecialchars($data['client_secret'], ENT_QUOTES, 'UTF-8') ?>">

                <?= HTMLHelper::_('form.token'); ?>
            </form>
        </div>

        <div class="col-12 col-xl-4">
            <form action="index.php?option=com_clinicchatbot&task=config.uploadPrices" method="post"
                enctype="multipart/form-data">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Prisimport fra Excel</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Upload en <strong>.xlsx</strong>-fil med priser på
                            Behandlinger.
                        </p>

                        <div class="mb-3">
                            <label for="prices_xlsx" class="form-label">XLSX-fil</label>
                            <input type="file" class="form-control" id="prices_xlsx" name="prices_xlsx"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required <?= $isRegistered ? '' : 'disabled' ?>>
                        </div>

                        <?php if (!$isRegistered): ?>
                            <div class="alert alert-warning mb-0">
                                Registrer klinikken først, så pluginet automatisk får credentials til prisupload og chat.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-outline-secondary w-100" <?= $isRegistered ? '' : 'disabled' ?>>
                            Upload priser
                        </button>
                    </div>
                </div>

                <?= HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>