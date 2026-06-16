<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="header">
    <div class="container">
        <div class="header-inner">
            <a href="<?php echo home_url(); ?>" class="logo">
                Squiggly<span>Marketing</span>
            </a>
            
            <nav class="nav">
                <a href="<?php echo home_url('/services'); ?>">Services</a>
                <a href="<?php echo home_url('/pricing'); ?>">Pricing</a>
                <a href="<?php echo home_url('/about'); ?>">About</a>
                <a href="<?php echo home_url('/quiz'); ?>" class="btn btn-primary">Take the Quiz</a>
            </nav>
            
            <div class="mobile-toggle" onclick="document.querySelector('.nav').classList.toggle('active')">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</div>