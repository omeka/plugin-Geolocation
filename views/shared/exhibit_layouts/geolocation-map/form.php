<?php
$formStem = $block->getFormStem();
$options = $block->getOptions();
?>
<div class="selected-items">
    <h4><?php echo __('Items'); ?></h4>
    <?php echo $this->exhibitFormAttachments($block); ?>
</div>

<div class="layout-options">
    <div class="block-header drawer">
        <h4><?php echo __('Layout Options'); ?></h4>
        <button class="drawer-toggle" type="button" data-action-selector="opened" aria-expanded="true" aria-controls="<?php echo $formStem; ?>-layout-options" aria-label="<?php echo __('Show options'); ?>" title="<?php echo __('Show options'); ?>"><span class="icon"></span></button>
    </div>
    <div class="drawer-contents" id="<?php echo $formStem; ?>-layout-options">
        <div class="sequence-mode">
            <?php echo $this->formLabel($formStem . '[options][sequence]', __('Sequence mode')); ?>
            <?php echo $this->formCheckbox($formStem . '[options][sequence]', @$options['sequence'], [], ['1', '0']); ?>
            <p class="instructions"><?php echo __('Step through locations in the arranged order above.'); ?></p>
        </div>
    </div>
</div>
