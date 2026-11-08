<?php

declare(strict_types=1);

namespace App\Project;

use Credit\Core\Enum\CreditProjectTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ProjectPageRegistry
{
    /** @var list<ProjectPageInterface> */
    private array $pages;

    /**
     * @param iterable<ProjectPageInterface> $pages
     */
    public function __construct(
        #[AutowireIterator(ProjectPageInterface::TAG)]
        iterable $pages,
    ) {
        $pages = [...$pages];
        usort($pages, static fn (ProjectPageInterface $a, ProjectPageInterface $b): int => $a->getPosition() <=> $b->getPosition());
        $this->pages = $pages;
    }

    /** @return list<ProjectPageInterface> */
    public function all(): array
    {
        return $this->pages;
    }

    public function findBySlug(string $slug): ?ProjectPageInterface
    {
        foreach ($this->pages as $page) {
            if ($page->getSlug() === $slug) {
                return $page;
            }
        }

        return null;
    }

    public function findByProject(CreditProjectTypeEnum $project): ?ProjectPageInterface
    {
        foreach ($this->pages as $page) {
            if ($page->getProjectType() === $project) {
                return $page;
            }
        }

        return null;
    }
}
