<?php

namespace App\Domain\Publishing;

use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\ExpertiseArea;
use App\Models\Language;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

final class EditorialMutationGuard implements ShouldHandleEventsAfterCommit
{
    /** @var list<string> */
    private const SENSITIVE_ATTRIBUTES = [
        'status', 'is_visible', 'published_at', 'key', 'key_locked',
        'photo_private_path', 'photo_public_path', 'photo_mime', 'photo_size', 'photo_alt_es', 'photo_alt_en',
        'icon_private_path', 'icon_public_path', 'icon_mime', 'icon_size',
        'private_path', 'mime', 'size',
    ];

    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
        private readonly PublicContentDependencies $dependencies,
    ) {}

    public function creating(Model $content): void
    {
        $this->assertHighlightMutationContext($content);
        $this->assertGalleryMutationContext($content);
        if ($content instanceof ProjectImage) {
            return;
        }
        if ($content instanceof ExperienceHighlight) {
            return;
        }

        $this->assertLocalState($content);
        if ($this->isPublished($content)) {
            throw new \LogicException('Managed content must be created as a draft through the publication action.');
        }
    }

    public function updating(Model $content): void
    {
        $this->assertHighlightMutationContext($content);
        $this->assertGalleryMutationContext($content);
        if ($content instanceof ProjectImage) {
            return;
        }
        if ($content instanceof ExperienceHighlight) {
            return;
        }

        $this->assertLocalState($content);

        if ($this->hasSensitiveChanges($content) && ! $this->context->isActive()) {
            throw new \LogicException('Sensitive editorial mutation must be performed through a domain action.');
        }

        if ($this->isPublished($content)) {
            $this->validator->assertPublishable($content);
        }
    }

    public function deleting(Model $content): void
    {
        $this->assertHighlightMutationContext($content);
        $this->assertGalleryMutationContext($content);

        if (! $this->context->isActive()) {
            throw new \LogicException('Sensitive editorial mutation must be performed through a domain action.');
        }
    }

    public function created(Model $content): void
    {
        $this->invalidate($content);
    }

    public function updated(Model $content): void
    {
        $this->invalidate($content);
    }

    public function deleted(Model $content): void
    {
        $this->invalidate($content);
    }

    private function assertLocalState(Model $content): void
    {
        $status = $content->getAttribute('status');
        $statusValue = $status instanceof PublicationStatus ? $status->value : $status;
        $visible = (bool) $content->getAttribute('is_visible');
        $publishedAt = $content->getAttribute('published_at');

        if (($statusValue === PublicationStatus::Draft->value && ($visible || $publishedAt !== null)) || ($statusValue === PublicationStatus::Published->value && $publishedAt === null)) {
            throw new \LogicException('Impossible editorial publication state.');
        }

        if ($statusValue === PublicationStatus::Published->value && array_key_exists('key_locked', $content->getAttributes()) && ! (bool) $content->getAttribute('key_locked')) {
            throw new \LogicException('Impossible editorial key-lock state.');
        }
    }

    private function hasSensitiveChanges(Model $content): bool
    {
        foreach (self::SENSITIVE_ATTRIBUTES as $attribute) {
            if ($content->isDirty($attribute)) {
                return true;
            }
        }

        return false;
    }

    private function assertHighlightMutationContext(Model $content): void
    {
        if ($content instanceof ExperienceHighlight && ! $this->context->isAggregateActive()) {
            throw new \LogicException('Experience highlights must be changed through the aggregate mutation context.');
        }
    }

    private function assertGalleryMutationContext(Model $content): void
    {
        if ($content instanceof ProjectImage && ! $this->context->isActive()) {
            throw new \LogicException('Project images must be changed through a domain action.');
        }
    }

    private function isPublished(Model $content): bool
    {
        return $content->getAttribute('status') === PublicationStatus::Published
            || $content->getAttribute('status') === PublicationStatus::Published->value;
    }

    private function invalidate(Model $content): void
    {
        if (! $this->isManagedContent($content) || $this->context->isActive()) {
            return;
        }

        $this->cache->invalidate($this->dependencies->for($content));
    }

    private function isManagedContent(Model $content): bool
    {
        return in_array($content::class, [
            Profile::class, SiteConfiguration::class, Experience::class, ExperienceHighlight::class,
            WorkCase::class, Project::class, ProjectImage::class, Technology::class, ExpertiseArea::class,
            WorkPrinciple::class, ProfessionalLink::class, CvDocument::class, EducationEntry::class, Language::class,
        ], true);
    }
}
