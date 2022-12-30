<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/** @var Controller $controller */
$controller = controller();

/** @var string $builderId */
$builderId = (string)$controller->getData('builderId');

/** @var string $modelName */
$modelName = (string)$controller->getData('modelName');

/** @var array $json */
$json = (array)$controller->getData('json');

/** @var array $options */
$options = (array)$controller->getData('options');

/** @var EmailTemplateBuilderExt $extension */
$extension = $controller->getData('extension');

?>
<div id="builder_<?php echo $builderId; ?>"></div>
<textarea name="<?php echo $modelName; ?>[content_json]" id="<?php echo $builderId; ?>_json" style="display: none"></textarea>
<script>
    jQuery(document).ready(function($){
        (function(){
            var params = {
                builderId : '<?php echo $builderId; ?>',
                options   :  <?php echo json_encode($options); ?>,
                json      :  <?php echo json_encode($json); ?>
            };
            var builderHandler = new TemplateBuilderHandler(params);

            $(document).on('click', '#btn_' + params.builderId, function(){
                builderHandler.toggle();
                return false;
            });

            if (builderHandler.shouldOpen()) {
                setTimeout(function(){
                    builderHandler.open();
                }, 1000);
            }

            $('#btn_' + params.builderId).closest('form').on('submit', function(){
                if (builderHandler.isEnabled()) {
                    $('#<?php echo $builderId; ?>_json').val(builderHandler.getInstance().getJson());
                    CKEDITOR.instances['<?php echo $builderId; ?>'].setData(builderHandler.getInstance().getHtml());
                }
            });

            $('#builder_<?php echo $builderId; ?>').on('templateBuilderHandler.afterClose', function(e, data){
                $('#<?php echo $builderId; ?>_json').val(data.json);
            });
        })()
    });
</script>

<div class="modal modal-info fade" id="page-info-toggle-template-builder" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
            </div>
            <div class="modal-body">
				<?php echo $extension->t('The template you create with the builder must be modified only using the builder.'); ?><br />
                <?php echo $extension->t('Do not modify the template outside the builder, it will break.'); ?>
            </div>
        </div>
    </div>
</div>
