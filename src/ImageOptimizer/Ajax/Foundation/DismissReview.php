<?php

namespace A3020\ImageOptimizer\Ajax\Foundation;

use A3020\ImageOptimizer\Controller\AjaxController;

class DismissReview extends AjaxController
{
    public function view()
    {
        $this->config->save('image_optimizer.foundation.review.is_dismissed', true);
    }
}
