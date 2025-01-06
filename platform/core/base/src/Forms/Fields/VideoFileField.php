<?php 
namespace Botble\Ecommerce\Forms\Fields;

use Botble\Base\Forms\Fields\FileField;

class VideoFileField extends FileField
{
    protected function initialize()
    {
        // You can set default configurations here if needed
        $this->setAttribute('accept', 'video/*'); // Accept only video files
        parent::initialize();
    }

    public function getTemplate()
    {
        return 'plugins/ecommerce::forms.fields.video-file'; // Path to your custom view
    }
}
