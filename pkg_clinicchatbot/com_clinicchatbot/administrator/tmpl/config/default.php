<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$data = $this->paramsData;
?>

<div class="container-fluid">
    <div class="mb-4">
        <h1 class="mb-1">Clinic Chatbot</h1>
        <p class="text-muted mb-0">
            Konfigurer klinikkens chatbot og importer prisark fra Excel.
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
                                <label for="clinic_id" class="form-label">Clinic ID</label>
                                <input type="text" class="form-control" id="clinic_id" name="jform[clinic_id]"
                                    value="<?= htmlspecialchars($data['clinic_id'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

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

                            <div class="col-md-6">
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
                                    Brug dette felt til interne noter, særlige behandlinger og klinikspecifik viden.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Backend</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="backend_url" class="form-label">Backend URL</label>
                                <input type="url" class="form-control" id="backend_url" name="jform[backend_url]"
                                    value="<?= htmlspecialchars($data['backend_url'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="form-text">
                                    Brug din chat-endpoint her, fx <code>http://localhost:3000/api/chat</code>.
                                    Prisimporten bruger automatisk <code>/api/clinic/prices</code>.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Client ID</label>
                                <input type="text" class="form-control" id="client_id" name="jform[client_id]"
                                    value="<?= htmlspecialchars($data['client_id'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="client_secret" class="form-label">Client Secret</label>
                                <input type="password" class="form-control" id="client_secret"
                                    name="jform[client_secret]"
                                    value="<?= htmlspecialchars($data['client_secret'], ENT_QUOTES, 'UTF-8') ?>"
                                    required>
                            </div>

                            <div class="col-md-4">
                                <label for="timeout_seconds" class="form-label">Timeout (sekunder)</label>
                                <input type="number" class="form-control" id="timeout_seconds"
                                    name="jform[timeout_seconds]"
                                    value="<?= htmlspecialchars($data['timeout_seconds'], ENT_QUOTES, 'UTF-8') ?>"
                                    min="1" max="60" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-primary">Gem indstillinger</button>
                    </div>
                </div>

                <?= HTMLHelper::_('form.token'); ?>
            </form>
        </div>

        <div class="col-12 col-xl-4">
            <form action="index.php?option=com_clinicchatbot&task=config.uploadPrices" method="post"
                enctype="multipart/form-data">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Prisimport fra Excel</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Upload en <strong>.xlsx</strong>-fil med arkene
                            <strong>Behandlinger</strong> og
                            <strong>Danmark ekstra tilskud</strong>.
                        </p>

                        <div class="mb-3">
                            <label for="prices_xlsx" class="form-label">XLSX-fil</label>
                            <input type="file" class="form-control" id="prices_xlsx" name="prices_xlsx"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required>
                        </div>

                        <div class="small text-muted">
                            Importen læser Excel-filen i Joomla, omdanner den til normaliseret JSON
                            og sender derefter data sikkert til backend.
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-success">Importer priser</button>
                    </div>
                </div>

                <?= HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>