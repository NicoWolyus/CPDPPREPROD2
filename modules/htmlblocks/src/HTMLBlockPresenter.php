<?php

class HTMLBlockPresenter
{
    private $link;
    private $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function present(HTMLBlock $cmsBlock)
    {
        return array(
            'id' => (int)$cmsBlock->id,
            'title' => $cmsBlock->name,
            'hook' => (new Hook((int)$cmsBlock->id_hook))->name,
            'position' => $cmsBlock->position,
            'content' => $cmsBlock->content[(int)$this->language->id]
        );
    }
}
