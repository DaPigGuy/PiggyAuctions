# PiggyAuctions [![Poggit-CI](https://poggit.pmmp.io/shield.dl/PiggyAuctions)](https://poggit.pmmp.io/p/PiggyAuctions) [![Discord](https://img.shields.io/discord/330850307607363585?logo=discord)](https://discord.gg/qmnDsSD)

PiggyAuctions is an open-sourced auction house plugin for [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) allowing players to place auctions and bid on items.

Why use PiggyAuctions over other competitors?
1. **BRAND LOYALTY!!!** PiggyAuctions is a quality Piggy-flavored plugin refined throughout generations of the Sus genus. You won't get to experience this authentic rich and savory flavor anywhere else. And, it's *free* bacon, what else could you ask for?
2. PiggyAuctions has bidding, searching, sorting, auto-refreshing, pagination, etc.
3. Oink? You're not going to install such an amazing plugin? 🐷
4. Sloths are scary.

## Prerequisites
* Basic knowledge on how to install plugins from Poggit Releases and/or Poggit CI
* PMMP 4.0.0+
* mysql & sqlite3 PHP extensions (should already exist within your PHP binaries)

## InvCrashFix is still needed!

- Menus may not work without InvCrashFix. [Download here](https://poggit.pmmp.io/r/139694/InvCrashFix_dev-4.phar)

## Supported Economy Providers

* [EconomyAPI](https://poggit.pmmp.io/p/EconomyAPI) by onebone/poggit-orphanage
* [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) by cooldogedev
* Experience (PMMP)

## Installation & Setup
1. Install the plugin from Poggit.
2. (Optional) Setup the data provider that PiggyAuctions will be using. By default, PiggyAuctions will use SQLite3 which requires no additional setup. If you would like to use MySQL instead, change `database.type` from `sqlite` to `mysql` & enter your MySQL credentials under `database.mysql`.
3. (Optional) Setup your economy provider. If using EconomyAPI, this step can be skipped. Otherwise, change `economy.provider` to the name of the economy plugin being used, or `xp` for PMMP Player EXP.
4. (Optional) Certain user inputs for creating and bidding on auctions can be configured. By default, the duration limit is 14 days, while bid & starting bid limit are the 32 bit integer max. We recommend not allowing values over the 32 bit integer max (2^31 or 2147483648).
5. (Optional) You may configure messages in the `message.yml` file.
6. You're done! Start your server and begin auctioning items.

## Commands
| Command                  | Description                       | Permissions                          | Aliases |
|--------------------------|-----------------------------------|--------------------------------------|---------|
| `/auctionhouse`          | Opens the auction house           | `piggyauctions.command.auctionhouse` | `/ah`   |
| `/auctionhouse [player]` | View a specific player's auctions | `piggyauctions.command.auctionhouse` | `/ah`   |

## Permissions
| Permissions                          | Description                                                   | Default |
|--------------------------------------|---------------------------------------------------------------|---------|
| `piggyauctions`                      | Allows usage of all PiggyAuctions features                    | `op`    |
| `piggyauctions.command`              | Allow usage of all PiggyAuctions commands                     | `op`    |
| `piggyauctions.command.auctionhouse` | Allow usage of the /auctionhouse command                      | `true`  |
| `piggyauctions.limit.{NUMBER}`       | Imposes a limit on amount of concurrent auctions for a player | `false` |

## Issue Reporting
* If you experience an unexpected non-crash behavior with PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=bug&template=bug_report.md&title=).
* If you experience a crash in PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=bug&template=crash.md&title=).
* If you would like to suggest a feature to be added to PiggyAuctions, click [here](https://github.com/DaPigGuy/PiggyAuctions/issues/new?assignees=DaPigGuy&labels=suggestion&template=suggestion.md&title=).
* If you require support, please join our discord server [here](https://discord.gg/qmnDsSD).
* Do not file any issues related to outdated API version; we will resolve such issues as soon as possible.
* We do not support any spoons of PocketMine-MP. Anything to do with spoons (Issues or PRs) will be ignored.
  * This includes plugins that modify PocketMine-MP's behavior directly, such as TeaSpoon.

## License
```
   Copyright 2019 DaPigGuy

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
