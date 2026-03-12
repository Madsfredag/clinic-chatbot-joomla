<?php

defined('_JEXEC') or die;

$data = $this->paramsData;
?>

<div class="container-fluid">
    <h1>Clinic Chatbot</h1>

    <form action="index.php?option=com_clinicchatbot&task=config.save" method="post">
        <h2>Klinikoplysninger</h2>

        <div class="mb-3">
            <label for="clinic_id">Clinic ID</label><br>
            <input type="text" id="clinic_id" name="jform[clinic_id]"
                value="<?= htmlspecialchars($data['clinic_id'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="clinic_name">Kliniknavn</label><br>
            <input type="text" id="clinic_name" name="jform[clinic_name]"
                value="<?= htmlspecialchars($data['clinic_name'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="clinic_phone">Telefonnummer</label><br>
            <input type="text" id="clinic_phone" name="jform[clinic_phone]"
                value="<?= htmlspecialchars($data['clinic_phone'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="clinic_address">Adresse</label><br>
            <input type="text" id="clinic_address" name="jform[clinic_address]"
                value="<?= htmlspecialchars($data['clinic_address'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="booking_url">Booking URL</label><br>
            <input type="url" id="booking_url" name="jform[booking_url]"
                value="<?= htmlspecialchars($data['booking_url'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="welcome_message">Velkomstbesked</label><br>
            <textarea id="welcome_message" name="jform[welcome_message]" rows="4"
                required><?= htmlspecialchars($data['welcome_message'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="clinic_knowledge">Klinikviden</label><br>
            <textarea id="clinic_knowledge" name="jform[clinic_knowledge]"
                rows="10"><?= htmlspecialchars($data['clinic_knowledge'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <h2>Backend</h2>

        <div class="mb-3">
            <label for="backend_url">Backend URL</label><br>
            <input type="url" id="backend_url" name="jform[backend_url]"
                value="<?= htmlspecialchars($data['backend_url'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="client_id">Client ID</label><br>
            <input type="text" id="client_id" name="jform[client_id]"
                value="<?= htmlspecialchars($data['client_id'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="client_secret">Client Secret</label><br>
            <input type="text" id="client_secret" name="jform[client_secret]"
                value="<?= htmlspecialchars($data['client_secret'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-3">
            <label for="timeout_seconds">Timeout (sekunder)</label><br>
            <input type="number" id="timeout_seconds" name="jform[timeout_seconds]"
                value="<?= htmlspecialchars($data['timeout_seconds'], ENT_QUOTES, 'UTF-8') ?>" min="1" max="60"
                required>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Gem indstillinger</button>
        </div>
    </form>
</div>