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

namespace Markocupic\ContaoSchoolTest\Task;

/**
 * Turns stored tasks into what the browser may see: everything except solutions and solution paths.
 */
final class TaskPresenter
{
    private const PUBLIC_ANSWER_KEYS = ['type', 'suffix', 'est', 'noWhole', 'mode', 'name', 'draw', 'fields', 'svg', 'kreuz'];

    private readonly TaskTranslator $translator;

    public function __construct(TaskTranslator|null $translator = null)
    {
        $this->translator = $translator ?? new TaskTranslator();
    }

    /**
     * @param string $lang language chosen by the student (de, fr, it, en)
     */
    public function toPublic(array $sections, string $lang = TaskTranslator::DEFAULT): array
    {
        $t = $this->translator;
        $out = [];

        foreach ($sections as $si => $section) {
            $tasks = [];

            foreach ($section['tasks'] as $ti => $task) {
                $a = array_intersect_key($task['a'], array_flip(self::PUBLIC_ANSWER_KEYS));

                if (isset($a['suffix'])) {
                    $a['suffix'] = $t->units((string) $a['suffix'], $lang);
                }

                if (isset($a['name'])) {
                    $a['name'] = $t->text((string) $a['name'], $lang);
                }

                // Terms: decimal comma in the prompts (French, Italian)
                if ('terms' === $task['a']['type']) {
                    $a['fields'] = array_map(static fn (array $f): array => isset($f['prompt']) ? ['prompt' => $t->decimals((string) $f['prompt'], $lang)] + $f : $f, $a['fields']);
                }

                if ('table' === $task['a']['type']) {
                    $a['cols'] = array_map(
                        static fn (array $c): array => array_intersect_key($c, array_flip(['ref', 'ask', 'top', 'bot', 'unit'])),
                        $task['a']['cols'],
                    );

                    // The cell that is asked for has no text
                    foreach ($a['cols'] as &$c) {
                        if (isset($c['ask'])) {
                            unset($c[$c['ask']]);
                            $c['unit'] = $t->units((string) $c['unit'], $lang);
                        }

                        foreach (['top', 'bot'] as $row) {
                            if (isset($c[$row])) {
                                $c[$row] = $t->units((string) $c[$row], $lang);
                            }
                        }
                    }
                    unset($c);
                }

                $tasks[] = [
                    'id' => self::key($si, $ti),
                    'q' => $t->question((string) $task['q'], $lang),
                    'a' => $a,
                    'points' => $task['points'] ?? 1,
                ];
            }

            $out[] = [
                'title' => $t->text((string) $section['title'], $lang),
                'instruction' => $t->text((string) $section['instruction'], $lang),
                'tasks' => $tasks,
            ];
        }

        return $out;
    }

    public static function key(int $section, int $task): string
    {
        return 's'.$section.'t'.$task;
    }
}
