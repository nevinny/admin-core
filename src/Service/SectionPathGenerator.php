<?php

namespace AdminCore\Service;

use AdminCore\Entity\Main;

final class SectionPathGenerator
{
    public function generateFullPath($entity): string
    {
//        dd($entity);
        if($entity instanceof Main) {
            $parts = [];
            $current = $entity;
            while ($current !== null) {
                array_unshift($parts, $current->getSlug());
                $current = $current->getParent();
            }
            return '/' . implode('/', $parts);
        }
        return '/';
    }
}
