<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\tasks;

use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class CheckUpdatesTask extends AsyncTask
{
    /** @var string */
    private $version;
    /** @var string */
    private $api;

    public function __construct(string $version, string $api)
    {
        $this->version = $version;
        $this->api = $api;
    }

    public function onRun(): void
    {
        $releases = Internet::getURL("https://poggit.pmmp.io/releases.json?name=PiggyAuctions");
        if (is_string($releases)) {
            $data = json_decode($releases, true);
            if ($this->isLatestVersion($data[0]["version"])) {
                if ($this->isAPICompatible($data[0]["api"][0])) {
                    $this->setResult($releases);
                }
            }
        }
    }

    public function isLatestVersion(string $version): bool
    {
        $versionInformation = explode(".", $version);
        $currentVersionInformation = explode(".", $this->version);
        if ($versionInformation[0] > $currentVersionInformation[0]) return true;
        if ($versionInformation[0] === $currentVersionInformation[0]) {
            if ($versionInformation[1] > $currentVersionInformation[1]) return true;
            if ($versionInformation[1] === $currentVersionInformation[1] && $versionInformation[2] > $currentVersionInformation[2]) return true;
        }
        return false;
    }

    public function isAPICompatible(array $range): bool
    {
        $lowestAPI = $range["from"];
        $highestAPI = $range["to"];
        $currentAPIInformation = explode(".", $this->api);
        $lowestAPIInformation = explode(".", $lowestAPI);
        $highestAPIInformation = explode(".", $highestAPI);
        if (
            $currentAPIInformation[0] >= $lowestAPIInformation[0] && $currentAPIInformation[0] <= $highestAPIInformation[0] &&
            $currentAPIInformation[1] >= $lowestAPIInformation[1] && $currentAPIInformation[1] <= $highestAPIInformation[1] &&
            $currentAPIInformation[2] >= $lowestAPIInformation[2] && $currentAPIInformation[2] <= $highestAPIInformation[2]
        ) {
            return true;
        }
        return false;
    }

    public function onCompletion(Server $server): void
    {
        if ($this->getResult() !== null) {
            $plugin = $server->getPluginManager()->getPlugin("PiggyAuctions");
            if ($plugin instanceof PiggyAuctions && $plugin->isEnabled()) {
                $data = json_decode($this->getResult(), true);
                $plugin->getLogger()->info("PiggyAuctions v" . $data[0]["version"] . " is available for download at " . $data[0]["artifact_url"] . "/PiggyAuctions.phar");
            }
        }
    }
}