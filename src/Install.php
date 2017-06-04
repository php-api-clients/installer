<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Throwable;
use function Composed\package;
use function igorw\get_in;

final class Install extends Command
{
    const COMMAND = 'install';

    /**
     * @var array
     */
    private $yaml;

    public function setYaml(array $yaml): Install
    {
        $this->yaml = $yaml;

        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->yaml === null) {
            throw new InvalidArgumentException('Missing configuration');
        }

        $style = new SymfonyStyle($input, $output);

        $style->newLine(2);
        $this->asciiArt($style);
        $style->newLine(2);

        $style->title($this->yaml['text']['welcome']);

        /**
         * Launchpad, the retry goto point.
         */
        retry:
        $style->section('Please answer the following questions.');

        $replacements = [];
        $table = [];

        foreach ($this->yaml['questions'] as $identifier => $question) {
            $replacements[$identifier] = $style->ask(
                $question['question'],
                get_in($question, ['default'], '')
            );

            if (empty($replacements[$identifier]) ||
                (isset($question['validate']) && is_callable($question['validate']))
            ) {
                while (!$this->validate($question['validate'], $replacements[$identifier]) ||
                    empty($replacements[$identifier])
                ) {
                    $replacements[$identifier] = $style->ask(
                        'Invalid response please try again, ' . $question['question'],
                        get_in($question, ['default'], '')
                    );
                }
            }

            $table[] = [
                $question['description'],
                $replacements[$identifier],
            ];
        }

        $style->section('Summary:');
        $style->table(
            [
                'What',
                'Value',
            ],
            $table
        );

        $installNow = $style->choice(
            'All settings correct?',
            [
                'y' => 'Yes',
                'n' => 'Change settings',
                'q' => 'Cancel installation',
            ],
            'Yes'
        );

        switch (strtolower($installNow)) {
            case 'y':
            {
                $style->text('Creating your middleware package now.');
                /** @var callable $operation */
                foreach ($this->yaml['operations'] as $operation) {
                    $operation()->operate($replacements, $this->yaml['config'] ?? [], $style);
                }
                $style->section('Package creation has been successfully.');
                $style->text('Next up we\'re running composer update twice to ensure all traces of this installer are gone.');
                $style->text('(The first time to update composer.lock, and the second time to update the autoloader.)');
                $style->text('After which your new package is ready to be developed.');
                $style->success('Installer done.');
                break;
            }

            case 'n':
            {
                /**
                 * Retry, goto launchpad.
                 */
                goto retry;
                break;
            }

            case 'q':
            {
                $style->error('Installation canceled.');

                return 9;
            }
        }

        return 0;
    }

    private function validate(callable $validator, $input): bool
    {
        try {
            return $validator($input);
        } catch (Throwable $t) {
            return false;
        }
    }

    private function asciiArt(SymfonyStyle $style)
    {
        if (!isset($this->yaml['text']['ascii_art_file'])) {
            return;
        }

        $package = $this->yaml['package'];
        if (isset($this->yaml['text']['ascii_art_package'])) {
            $package = $this->yaml['text']['ascii_art_package'];
        }
        $path = package($package)->getPath() . DIRECTORY_SEPARATOR;

        $files = $this->yaml['text']['ascii_art_file'];
        if (!is_array($files)) {
            $files = [$files];
        }

        $sortedFiles = [];
        foreach ($files as $file) {
            $artWidth = 0;
            $contents = file($path . $file);
            foreach ($contents as $line) {
                $line = strip_tags($line);
                $lineLength = mb_strlen($line);
                if ($lineLength > $artWidth) {
                    $artWidth = $lineLength;
                }
            }
            $sortedFiles[$artWidth] = $path . $file;
        }

        $width = (new Terminal())->getWidth();
        $sortedFiles = array_filter($sortedFiles, function ($artWidth) use ($width) {
            return $width >= $artWidth;
        }, ARRAY_FILTER_USE_KEY);

        krsort($sortedFiles);
        $file = current($sortedFiles);

        foreach (file($file) as $line) {
            $style->write($line);
        }
    }
}
