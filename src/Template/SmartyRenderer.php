<?php

declare(strict_types=1);

namespace LovelyWedding\Template;

use Smarty\Smarty;

final class SmartyRenderer
{
    private readonly Smarty $smarty;

    public function __construct(string $templatesDir, string $compileDir)
    {
        if (!is_dir($compileDir)) {
            @mkdir($compileDir, 0775, true);
        }
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($templatesDir);
        $this->smarty->setCompileDir($compileDir);
        // Auto-escape: every {$var} output is htmlspecialchars'd unless |raw or {nofilter} used.
        $this->smarty->escape_html = true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $this->smarty->assign($data);

        return (string) $this->smarty->fetch($template);
    }
}
