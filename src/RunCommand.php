<?php

namespace Andig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class RunCommand extends Command
{
    use ConfigTrait;

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Download, convert and upload - all in one')
            ->addOption('image', 'i', InputOption::VALUE_NONE, 'download images');

        $this->addConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input);

        // we want to check for image upload show stoppers as early as possible
        if ($input->getOption('image')) {
            $this->uploadImagePreconditions($this->config['fritzbox'], $this->config['phonebook']);
        }
        
        /**
          * begin of insert branch next
          * compare timestamp of CardDAV against last update on Fritz!Box
          */
        $lastUpdate = 0;
        $latestMod  = 0;
        $recentPhonebook = downloadPhonebookSOAP($this->config);           // is needed for forecedupload > 1 as well

        if ($this->config['phonebook']['forcedupload'] < 2) {
            error_log("Determine the last change of the FRITZ!Box phonebook");
            $lastUpdate = $recentPhonebook->phonebook->timestamp;          // get timestamp from phonebook
            error_log("Determine the last change(s) on the CardDAV server(s)");            

            foreach ($this->config['server'] as $server) {                 // determine the youngest modification date
                $backend = backendProvider($server);
                $timeCache = getLastModification ($backend);
                if ($timeCache > $latestMod) {
                    $latestMod = $timeCache;
                }
            }
        }
        if ($lastUpdate > $latestMod) {
            error_log("Your Fritz!Box phonebook is newer than the contacts on the CardDAV server");
        }
        else {
            // end of insert branch next
            $quantity = 0;
            $vcards = [];
            $xcards = [];
            $substitutes = ($input->getOption('image')) ? ['PHOTO'] : [];

            foreach ($this->config['server'] as $server) {
                error_log("Downloading vCard(s) from account ".$server['user']);
                $backend = backendProvider($server);

                $progress = new ProgressBar($output);
                $progress->start();
                $xcards = download($backend, $substitutes, function () use ($progress) {
                    $progress->advance();
                });
                $progress->finish();

                $vcards = array_merge($vcards, $xcards);
                $quantity += count($xcards);
                error_log(sprintf("\nDownloaded %d vCard(s)", $quantity));
            }

            // dissolve
            error_log("Dissolving groups (e.g. iCloud)");
            $cards = dissolveGroups($vcards);
            $remain = count($cards);
            error_log(sprintf("Dissolved %d group(s)", $quantity - $remain));

            // filter
            error_log(sprintf("Filtering %d vCard(s)", $remain));
            $filters = $this->config['filters'];
            $filtered = filter($cards, $filters);
            error_log(sprintf("Filtered out %d vCard(s)", $remain - count($filtered)));

            // image upload
            if ($input->getOption('image')) {
                error_log("Detaching and uploading image(s)");
                $progress = new ProgressBar($output);
                $progress->start(count($filtered));
                $pictures = uploadImages($filtered, $this->config['fritzbox'], $this->config['phonebook'], function () use ($progress) {
                    $progress->advance();
                });
                $progress->finish();

                if ($pictures) {
                    error_log(sprintf("Uploaded/refreshed %d of %d image file(s)", $pictures[0], $pictures[1]));
                }
            } else {
                unset($this->config['phonebook']['imagepath']);             // otherwise convert will set wrong links
            }

            // fritzbox format
            $xmlPhonebook = exportPhonebook($filtered, $this->config);
            error_log(sprintf(PHP_EOL."Converted %d vCard(s)", count($filtered)));
            if (!count($filtered)) {
                error_log("Phonebook empty - skipping upload");
                return null;
            }
            // upload
            error_log("Uploading");
            uploadPhonebook($xmlPhonebook, $this->config);
            error_log("Successful uploaded new Fritz!Box phonebook");
            
            /**
              *  begin of insert branch next
              * fax number upload
              */
            if ($this->config['phonebook']['forcedupload'] < 3) {
                error_log('Checking to back up newer contacts of the Fritz!Box');
                $i = checkUpdates($recentPhonebook, $xmlPhonebook, $this->config);
                if ($i) {
                    error_log(sprintf("Saved %d contact(s)", $i));
                }
            }

            if (isset($this->config['fritzbox']['fritzadr'])) {
                error_log('Selecting and uploading fax number(s) for FRITZ!fax');
                $i = uploadFritzAdr($xmlPhonebook, $this->config['fritzbox']);
                if ($i) {
                    error_log(sprintf("Uploaded %d fax number entries into fritzadr.dbf", $i));
                }
            }
        }
        // end of insert branch next
    }
    /**
     * checks if preconditions for upload images are OK
     *
     * @return            mixed     (true if all preconditions OK, error string otherwise)
     */
    private function uploadImagePreconditions($configFritz, $configPhonebook)
    {
        if (!function_exists("ftp_connect")) {
            throw new \Exception(
                <<<EOD
FTP functions not available in your PHP installation.
Image upload not possible (remove -i switch).
Ensure PHP was installed with --enable-ftp
Ensure php.ini does not list ftp_* functions in 'disable_functions'
In shell run: php -r \"phpinfo();\" | grep -i FTP"
EOD
            );
        }
        if (!$configFritz['fonpix']) {
            throw new \Exception(
                <<<EOD
config.php missing fritzbox/fonpix setting.
Image upload not possible (remove -i switch).
EOD
            );
        }
        if (!$configPhonebook['imagepath']) {
            throw new \Exception(
                <<<EOD
config.php missing phonebook/imagepath setting.
Image upload not possible (remove -i switch).
EOD
            );
        }
        if ($configFritz['user'] == 'dslf-conf') {
            throw new \Exception(
                <<<EOD
TR-064 default user dslf-conf has no permission for ftp access.
Image upload not possible (remove -i switch).
EOD
            );
        }
    }
}