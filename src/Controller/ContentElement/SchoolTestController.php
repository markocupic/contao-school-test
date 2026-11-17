<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-school-test
 */

namespace Markocupic\ContaoSchoolTest\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The student enters the five digit code here and solves the test.
 * Everything else happens in assets/js/school-test.js against the TestApiController.
 */
#[AsContentElement(self::TYPE, category: 'miscellaneous', template: 'content_element/school_test')]
class SchoolTestController extends AbstractContentElementController
{
    public const TYPE = 'school_test';

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $urls = [];

        foreach (['start', 'resume', 'continue', 'save', 'lock', 'submit'] as $action) {
            $urls[$action] = $this->generateUrl('markocupic_school_test_api', ['action' => $action]);
        }

        $template->set('api_urls', $urls);

        return $template->getResponse();
    }
}
