<?php
/**
 * CoreUI Layout Start
 * Includes head, sidebar, and header
 */
include __DIR__ . '/coreui_head.php';
?>

<div class="wrapper d-flex flex-column min-vh-100 bg-light">
    <?php include __DIR__ . '/coreui_sidebar.php'; ?>
    
    <div class="wrapper d-flex flex-column min-vh-100 bg-light">
        <?php include __DIR__ . '/coreui_header.php'; ?>
        
        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
