<?php

namespace App\Filament\Resources\ScheduledPostResource\Pages;

use App\Filament\Resources\ScheduledPostResource;
use App\Models\PostTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledPost extends CreateRecord
{
    protected static string $resource = ScheduledPostResource::class;

    public function mount(): void
    {
        parent::mount();

        $templateId = request()->query('template_id');
        if ($templateId) {
            $template = PostTemplate::find($templateId);
            if ($template) {
                $this->form->fill([
                    'content' => $template->content,
                    'images' => $template->images ?? [],
                    'settings' => [
                        'minDelay' => 30,
                        'maxDelay' => 120,
                        'spinEnabled' => true,
                        'seedLike' => true,
                        'seedComments' => $template->seed_comments
                            ? collect($template->seed_comments)->pluck('text')->filter()->values()->toArray()
                            : [],
                        'seedDelay' => 10,
                    ],
                ]);
                $template->incrementUsage();
            }
        }
    }
}
