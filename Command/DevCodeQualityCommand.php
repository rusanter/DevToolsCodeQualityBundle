<?php

namespace DevTools\CodeQualityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class DevCodeQualityCommand
 *
 * @package DevTools\CodeQualityBundle
 */
class DevCodeQualityCommand extends ContainerAwareCommand
{
    /** @var array of Process */
    protected $commandPool = array();

    /** @var string */
    protected $binPath;

    /** @var string */
    protected $inspectPath;

    /** @var string */
    protected $outputPath;

    /** @var string */
    protected $dataPath;

    /**
     * @param $type
     * @param $buffer
     */
    public function rawOutput($type, $buffer)
    {
        if (Process::ERR === $type) {
            echo 'ERR > '.$buffer.PHP_EOL;
        } else {
            echo $buffer;
        }
    }

    /**
     * Define command options
     */
    protected function configure()
    {
        $this
            ->setName('dev:code-quality')
            ->setDescription('Run QA tests')
            ->addOption('inspect-path', null, InputOption::VALUE_REQUIRED, 'Path where your code base is located')
            ->addOption('output-path', null, InputOption::VALUE_REQUIRED, 'Path where reports will be generated')
            ->addOption('bin-path', null, InputOption::VALUE_REQUIRED, 'Composer bin-dir', 'bin')
            ->addOption('skip-phploc', null, InputOption::VALUE_NONE, 'Disable PHPLOC')
            ->addOption('skip-pdepend', null, InputOption::VALUE_NONE, 'Disable PHP_Depend')
            ->addOption('skip-phpmd', null, InputOption::VALUE_NONE, 'Disable PHP Mess Detector')
            ->addOption('skip-phpcpd', null, InputOption::VALUE_NONE, 'Disable PHP Copy/Paste Detector')
            ->addOption('skip-phpcs', null, InputOption::VALUE_NONE, 'Disable PHP_CodeSniffer')
        ;
    }

    /**
     * Run selected checks
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // configuration
        $this->inspectPath = $input->getOption('inspect-path') !== null ?
            $input->getOption('inspect-path') :
            $this->getContainer()->getParameter('dev_tools_code_quality.inspect_path');
        $this->outputPath = $input->getOption('output-path') !== null ?
            $input->getOption('output-path') :
            $this->getContainer()->getParameter('dev_tools_code_quality.output_path');
        $this->dataPath = $this->outputPath.DIRECTORY_SEPARATOR.'data';
        $this->binPath = $input->getOption('bin-path');
        $features = $this->getContainer()->getParameter('dev_tools_code_quality.features');

        $this->ensurePaths($this->inspectPath, $this->dataPath);
        $this->prepareOutputDir($this->outputPath);

        // Create process for every defined and known feature
        foreach ($features as $feature) {
            $method = 'run'.ucfirst($feature);
            if (!$input->getOption('skip-'.$feature) && method_exists($this, $method)) {
                $this->$method();
            }
        }

        if (!$this->commandPool) {
            $output->writeln(
                '<comment>Nothing to run. Please check the bundle configuration or command arguments.</comment>'
            );

            return -1;
        }

        $exitCode = 0;
        foreach ($this->commandPool as $command) {
            /** @var Process $process */
            $process = $command['process'];

            $output->writeln('<info>'.$command['runMessage'].'</info>');
            $output->writeln('<info>$ '.$process->getCommandLine().'</info>');

            $command['exit'] = $process->run(array($this, 'rawOutput'));

            if ($command['exit']) {
                $output->writeln('<comment>Command completed with exit code '.$command['exit'].'</comment>');
                $exitCode = $command['exit'];
            } else {
                $output->writeln('<info>Command completed successfully</info>');
            }
        }

        return $exitCode;
    }

    /**
     * Create process for PHPLOC
     */
    protected function runPhploc()
    {
        $procBuilder = new ProcessBuilder();
        $procBuilder
            ->setPrefix($this->binPath.DIRECTORY_SEPARATOR.'phploc')
            ->setArguments(array(
                '--log-xml',
                $this->dataPath.DIRECTORY_SEPARATOR.'phploc.xml',
                $this->inspectPath,
            ));

        $this->commandPool[] = array(
            'runMessage' => 'Running PHPLOC',
            'process' => $procBuilder->getProcess(),
            'exit' => -1,
        );
    }

    /**
     * Create process for PHP_Depend
     */
    protected function runPdepend()
    {
        $procBuilder = new ProcessBuilder();
        $procBuilder
            ->setPrefix($this->binPath.DIRECTORY_SEPARATOR.'pdepend')
            ->setArguments(array(
                '--summary-xml='.$this->dataPath.DIRECTORY_SEPARATOR.'pdepend.xml',
                '--jdepend-chart='.$this->dataPath.DIRECTORY_SEPARATOR.'jdepend.svg',
                '--overview-pyramid='.$this->dataPath.DIRECTORY_SEPARATOR.'pyramid.svg',
                $this->inspectPath
            ));

        $this->commandPool[] = array(
            'runMessage' => 'Running PHP_Depend',
            'process' => $procBuilder->getProcess(),
            'exit' => -1,
        );
    }

    /**
     * Create process for PHP Mess Detector
     */
    protected function runPhpmd()
    {
        $procBuilder = new ProcessBuilder();
        $procBuilder
            ->setPrefix($this->binPath.DIRECTORY_SEPARATOR.'phpmd')
            ->setArguments(array(
                $this->inspectPath,
                'html',
                'cleancode,codesize,controversial,design,naming,unusedcode',
                '--reportfile',
                $this->dataPath.DIRECTORY_SEPARATOR.'phpmd.html',
            ));

        $this->commandPool[] = array(
            'runMessage' => 'Running PHP Mess Detector',
            'process' => $procBuilder->getProcess(),
            'exit' => -1,
        );
    }

    /**
     * Create process for PHP Copy/Paste Detector
     */
    protected function runPhpcpd()
    {
        $procBuilder = new ProcessBuilder();
        $procBuilder
            ->setPrefix($this->binPath.DIRECTORY_SEPARATOR.'phpcpd')
            ->setArguments(array(
                '--log-pmd='.$this->dataPath.DIRECTORY_SEPARATOR.'phpcpd.xml',
                $this->inspectPath,
            ));

        $this->commandPool[] = array(
            'runMessage' => 'Running PHP Copy/Paste Detector',
            'process' => $procBuilder->getProcess(),
            'exit' => -1,
        );
    }

    /**
     * Create process for PHP_CodeSniffer
     */
    protected function runPhpcs()
    {
        $procBuilder = new ProcessBuilder();
        $procBuilder
            ->setPrefix($this->binPath.DIRECTORY_SEPARATOR.'phpcs')
            ->setArguments(array(
                '--standard=PSR2',
                '--report=json',
                '--report-file='.$this->dataPath.DIRECTORY_SEPARATOR.'phpcs.json',
                $this->inspectPath,
            ));

        $this->commandPool[] = array(
            'runMessage' => 'Running PHP Mess Detector',
            'process' => $procBuilder->getProcess(),
            'exit' => -1,
        );
    }

    /**
     * Check if inspect path exists and ensure output directory is created
     *
     * @param $inspectPath
     * @param $outputPath
     *
     * @return bool
     */
    protected function ensurePaths(&$inspectPath, &$outputPath)
    {
        $cwd = getcwd();
        $inspectPath = $cwd.DIRECTORY_SEPARATOR.$inspectPath;
        $outputPath = $cwd.DIRECTORY_SEPARATOR.$outputPath;

        if (false === $real = realpath($inspectPath)) {
            throw new FileNotFoundException('Cannot access "'.$inspectPath.'"');
        }
        $inspectPath = $real;

        if (false === $real = realpath($outputPath)) {
            $fs = new Filesystem();
            $fs->mkdir($outputPath, 0755);

            if (false === $real = realpath($outputPath)) {
                throw new FileNotFoundException('Cannot create "'.$outputPath.'"');
            }
        }
        $outputPath = $real;

        return true;
    }

    /**
     * Copy html files from bundle resource to output dir
     *
     * @param $path
     */
    protected function prepareOutputDir($path)
    {
        $templatePath = str_replace('::', DIRECTORY_SEPARATOR, __DIR__.'::..::Resources::report::qa');

        $fs = new Filesystem();
        $fs->mirror($templatePath, $path, null, array(
            'override' => true,
            'copy_on_windows' => false,
            // do not remove unknown files because it can cause problems on misconfiguration
            'delete' => false,
        ));
    }
}
