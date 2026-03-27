<?php use function Harbor\View\view_partial;

?>

<div class="layout-app">
    <header>
        <?php if (($is_production ?? false) === true) { ?>
            <?php view_partial('partials/header_prod'); ?>
        <?php } else { ?>
            <?php view_partial('partials/header_dev'); ?>
        <?php } ?>
    </header>

    <main><?php echo $content; ?></main>

    <aside>
        <?php if (! empty($right_partial ?? '')) { ?>
            <?php view_partial($right_partial, $right_data ?? []); ?>
        <?php } ?>
    </aside>
</div>
