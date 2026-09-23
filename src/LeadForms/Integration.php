<?php

declare(strict_types=1);

namespace WPRC\Catalog\LeadForms;

defined('ABSPATH') || exit;

/** Public lead-form projection and transport owned by RC Catalog on www. */
final class Integration
{
    public function __construct(
        private readonly RemoteFormRepository $forms,
        private readonly FormRenderer $renderer,
        private readonly SubmissionProxy $submissions
    ) {
    }

    public function init(): void
    {
        $this->forms->init();
        $this->renderer->init();
        $this->submissions->init();
    }
}
