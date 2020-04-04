# PiggyAuctions [![Poggit-CI](https://poggit.pmmp.io/shield.dl/PiggyAuctions)](https://poggit.pmmp.io/p/PiggyAuctions) [![Discord](https://img.shields.io/discord/330850307607363585?logo=discord)](https://discord.gg/qmnDsSD)

PiggyAuctions is an open-sourced auction house plugin for [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) allowing players to place auctions and bid on items.

Why use PiggyAuctions over other competitors?
1. **BRAND LOYALTY!!!** PiggyAuctions is a quality Piggy-flavored plugin refined throughout generations of the Sus genus. You won't get to experience this authentic rich and savory flavor anywhere else. And, it's *free* bacon, what else could you ask for?
2. PiggyAuctions has bidding, searching, sorting, auto-refreshing, pagination, etc.
3. Oink? You're not going to install such an amazing plugin? 🐷
4. Sloths are scary.

## Supported Economy Plugins
* [EconomyAPI](https://github.com/onebone/EconomyS/tree/3.x/EconomyAPI) by onebone
* [MultiEconomy](https://github.com/TwistedAsylumMC/MultiEconomy) by TwistedAsylumMC
* Or, you can use player experience as a monetary value.

## Commands
| Command | Description | Permissions | Aliases |
| --- | --- | --- | --- |
| `/auctionhouse` | Opens the auction house | `piggyauctions.command.auctionhouse` | `/ah` |
| `/auctionhouse [player]` | View a specific player's auctions | `piggyauctions.command.auctionhouse` | `/ah` |

## Permissions
| Permissions | Description | Default |
| --- | --- | --- |
| `piggyauctions` | Allows usage of all PiggyAuctions features | `op` |
| `piggyauctions.command` | Allow usage of all PiggyAuctions commands | `op` |
| `piggyauctions.command.auctionhouse` | Allow usage of the /auctionhouse command | `true` |
| `piggyauctions.limit.{NUMBER}` | Imposes a limit on amount of concurrent auctions for a player | `false` |

## Issue Reporting
* If you experience an unexpected non-crash behavior with PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=bug&template=bug_report.md&title=).
* If you experience a crash in PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=bug&template=crash.md&title=).
* If you would like to suggest a feature to be added to PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=suggestion&template=suggestion.md&title=).
* If you require support, please join our discord server [here](https://discord.gg/qmnDsSD).
* Do not file any issues related to outdated API version; we will resolve such issues as soon as possible.
* We do not support any spoons of PocketMine-MP. Anything to do with spoons (Issues or PRs) will be ignored.
  * This includes plugins that modify PocketMine-MP's behavior directly, such as TeaSpoon.

## Information
* We do not support any spoons. Anything to do with spoons (Issues or PRs) will be ignored.
* We are using the following virions: [Commando](https://github.com/CortexPE/Commando), [InvMenu](https://github.com/Muqsit/InvMenu), [libasynql](https://github.com/poggit/libasynql), [libFormAPI](https://github.com/jojoe77777/FormAPI), and [libPiggyEconomy](https://github.com/DaPigGuy/libPiggyEconomy).
    * **You MUST use the pre-compiled phar from [Poggit-CI](https://poggit.pmmp.io/ci/DaPigGuy/PiggyAuctions/~) instead of GitHub.**
    * If you wish to run it via source, check out [DEVirion](https://github.com/poggit/devirion).
* Check out our [Discord Server](https://discord.gg/qmnDsSD) for additional plugin support.

## License
```
   Copyright 2019-2020 DaPigGuy

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

```