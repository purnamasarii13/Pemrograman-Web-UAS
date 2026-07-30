<?php
$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem Informasi Akademik Kampus">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div id="pageLoader" class="page-loader d-none" aria-hidden="true">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<div class="app-wrapper">
