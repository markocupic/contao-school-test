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
use Markocupic\ContaoSchoolTest\TestType\PracticeTestTypeInterface;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Practice mode without a test and without a code: the student picks a test type,
 * one of its topics and a difficulty. New random tasks every time, nothing is stored.
 * Answers are checked on the server and explained.
 */
#[AsContentElement(self::TYPE, category: 'miscellaneous', template: 'content_element/school_test_practice')]
class SchoolTestPracticeController extends AbstractContentElementController
{
    public const TYPE = 'school_test_practice';

    public function __construct(
        private readonly TestTypeRegistry $testTypes,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $types = [];

        foreach ($this->testTypes->all() as $alias => $type) {
            if ($type instanceof PracticeTestTypeInterface) {
                $types[] = ['alias' => $alias, 'area' => $type->getPracticeArea(), 'labels' => $type->getPracticeLabels(), 'topics' => $type->getPracticeTopics(), 'difficulty' => $type->hasPracticeDifficulty()];
            }
        }

        $template->set('practice', [
            'element' => (int) $model->id,
            'types' => $types,
            'urls' => [
                'new' => $this->generateUrl('markocupic_school_test_practice_api', ['action' => 'new']),
                'try' => $this->generateUrl('markocupic_school_test_practice_api', ['action' => 'try']),
                'check' => $this->generateUrl('markocupic_school_test_practice_api', ['action' => 'check']),
            ],
        ]);

        return $template->getResponse();
    }
}
