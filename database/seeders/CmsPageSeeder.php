<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CmsPage::truncate();

        $pages = [
            [
                'slug' => 'getting-started',
                'title' => 'Getting Started',
                'description' => 'A quick startup guide for the new player.',
                'hero_title' => 'Getting Started',
                'hero_description' => 'A quick startup guide for the new player.',
                'images' => [
                    [
                        'name' => 'empiredog',
                        'link' => 'https://www.deviantart.com/empiredog',
                        'path' => '/images/gettingstarted-art-empiredog.png',
                    ],
                    [
                        'name' => 'iiyell',
                        'link' => 'https://www.deviantart.com/iiyell',
                        'path' => '/images/gettingstarted-art-iiyell.png',
                    ],
                    [
                        'name' => 'moontearcove',
                        'link' => 'https://www.deviantart.com/moontearcove',
                        'path' => '/images/gettingstarted-art-moontearcove.png',
                    ],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/gettingstarted-banner-step1.png)',
                        'First, you\'ll want to read the [group rules](/rules) and join our [Discord](https://discord.gg/rArZNnkCfE). Discord is required to play on Rattlesnake Mountain, as we function across multiple art websites. The rules may also be quickly found on our Discord.',
                    ],
                    'box2' => [
                        '![](/images/gettingstarted-banner-step2.png)',
                        'Next, you\'ll want to receive the **@Rattlesnake** role by reacting to the post at the bottom of the #rules channel in our Discord. By obtaining this role, it indicates to our staff that you comprehend and can adhere to the group\'s rules.',
                        'Then, if you\'re so inclined, click the \"Join\" button on our DeviantArt group page. DeviantArt is not required for playing on Rattlesnake Mountain, however all art or literature posted on DeviantArt that involves your characters on Rattlesnake Mountain should be submitted to the group.',
                    ],
                    'box3' => [
                        '![](/images/gettingstarted-banner-step3.png)',
                        'Third, it\'s time to acquire your Welcome Package! This package will contain goodies that will help you get started. To obtain your Welcome Package, post the following form in the #join-up channel (this is also pinned in that channel).',
                        "**Your Preferred Name:** Sometimes our names differ from our art/literature account names. Make sure we get it right here.\n\n**Art/Literature Account Link:** Link your DeviantArt, Instagram, ToyHouse, etc here.\n\n**Preferred Pronouns:** Optional if you don't wish to disclose this information.\n\n**Recruited By\\*:** See the paragraph below.",
                        "**All Welcome Packages contain the following:**\n\n500 Scorpions\n\n1 White-Modifier Stone of your choice.\n\n1 Cream or 1 Pearl Stone of your choice.\n\n2 Randomized Stones for further character customization.\n\n2 Random Herbs for everyday use.\n\n2 Randomized Feathers for PvP.​",
                        '*\\* Were you recruited by another player? Sign you and your friend up for a Recruit-a-Friend package by simply listing their name in the form when you join. Note that players who spam-invite players (specifically players who join but never participate) to take advantage of this system will have their Recruit-A-Friend benefits removed permanently, including but not limited to all bonus stats removed from their characters and all additional scorpions removed/items revoked. **Please do not artificially inflate our player counts for your benefit!***',
                        "**Recruit-A-Friend packages contain the following in addition to the base Welcome Package:**\n\nA bonus of 100 Scorpions for you and your friend.\n\n2 Stones of your choice, each.\n\n2 Herbs of your choice, each.\n\n2 Feathers of your choice, each.\n\n+10 Scorpions per submission for 3 months of play, each.\n\n+1 extra stat per submission for 3 months of play, each.",
                    ],
                    'box4' => [
                        '![](/images/gettingstarted-banner-step4.png)',
                        'Fourth, it\'s time to design or acquire your first character! For more information, check out our [Character Handbook](/character-handbook) and [Character Creation](/character-upload) pages. To submit your designs, head on over to our #leader-designs channel in our Discord and use the form there.',
                        '![](/images/gettingstarted-banner-charactercreationalts.png)',
                        'If you do not wish to create or design your own character, Rattlesnake Mountain offers a few alternatives.',
                        "**I. Randomized Claimable**\n\na. We will randomly choose from a list of our current claimable Bachelor Stallions and Herd Mares and supply you with three options of each gender. You will then choose one horse, and they will become your main character.\n\n• In order to choose a horse, please post the form found in our #requests-and-updates.",
                        "**II. Adopt a Claimable**\n\na. You may [browse](https://www.deviantart.com/rattlesnakeadmin/gallery?q=claimable) through our available horses and pick out one that you would like to play! Our DeviantArt account houses the most up-to-date information on which horses are available for claim.\n\nb. The horse's personality, history, benefits, and detriments are able to be changed at your discretion. If you find a horse that you would like to adopt, please claim it in #requests-and-updates.",
                        "**III. Ask a Rattlesnake Designer**\n\na. You may ask an official Rattlesnake Designer to design you a horse or design a rolled genotype yourself. An admin will randomly roll you five phenotypes, and you will pick one for the designer to work with you on creating!\n\nb. Please keep in mind, a design may take one-two weeks, depending upon complexity and designer availability.\n\nc. In order to roll your phenotypes, please use the Randomized Claimable form in #requests-and-updates. A Designer may then be found for your chosen genotype in #rattlesnake-studio. All designs should be submitted in #leader-designs when you are finished creating your character.",
                    ],
                    'box5' => [
                        '![](/images/gettingstarted-banner-step5.png)',
                        "Fifth, after your design has been approved or selected and your herd's story thread has been created in the Discord, ensure your character is updated correctly on our DeviantArt account. A link to your character's upload will be posted in your upload approval response.",
                    ],
                    'box6' => [
                        '![](/images/gettingstarted-banner-step6.png)',
                        'Feel free to create a reference image of your character. Alternatively, writers may opt to create an origin story for their character or commission a reference. **Note that this reference image/origin story only counts toward gaining stats and does not count as a roll. To have your reference counted, please post it in your herd\'s story thread.**',
                    ],
                    'box7' => [
                        '![](/images/gettingstarted-banner-step7.png)',
                        "Last but not least, it's time to draw or write about your character for the first time and get started on your journey! If you want to get familiar with the lay of the land, our map will be of use. Should you require some inspiration, our initial prompts available for all characters are as follows:",
                        "**You wake one morning near an empty tank of water atop Rattlesnake Mountain and find that you are alone. The scent of man fills your nose, overpowering anything else. Unsettled, do you...**\n\n-> Head North, toward Pyramid Lake.\n\n-> Head Northeast, toward Rochester.\n\n-> Head Northwest, toward Lake Almanor.\n\n-> Head South, toward Carson City.\n\n-> Head Southeast, toward Grant Mountain.\n\n-> Head Southwest, toward Bald Mountain.\n\n-> Head East, toward Fallon.\n\n-> Head West, toward Tahoe National Forest.",
                        '*You will want to post this response - and all future responses - to the Thread created for your character on our Discord in #story-progression. Failure to post to your created Thread will likely result in your roll not being processed!*',
                        "**Every submission should include the form posted in the #herd-stories channel guidelines in the artist's comments of the piece/work. This form may also be found in your character's Story Thread.**",
                    ],
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => 'rules',
                'title' => 'Rules',
                'description' => 'Be sure to follow these at all times!',
                'hero_title' => 'Rules',
                'hero_description' => 'Be sure to follow these at all times!',
                'images' => [
                    ['name' => 'Adair408', 'link' => 'https://www.deviantart.com/adair408', 'path' => '/images/rules-art-adair408.png'],
                    ['name' => 'Jarchivist', 'link' => 'https://www.deviantart.com/jarchivist', 'path' => '/images/rules-art-jarchivist.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/rules-banner-rules.png)',
                        '**I. Respect**'."\n\n".'Rattlesnake Mountain and all its players and staff abide by the golden rule: "Do to others what you want them to do to you." This is, above all, the most important rule of the group. It isn\'t hard to treat people how you\'d like to be treated, and at the end of the day, Rattlesnake Mountain is a game. We come here to have fun.'."\n\na. ".'Mature, NSFW, or potentially triggering content should be marked accordingly.'."\n\nb. ".'Art theft in any way, shape, or form is not tolerated. That said, tracing references from stock photos is allowed as long as the piece complies with the stock artist\'s own set of rules. Credit does not need to be given unless the stock artist requires it.'."\n\nc. ".'Commissions are allowed, however the image must be submitted to the appropriate folder in the group with the Submission form filled out completely. Failure to do so will not result in a roll!'."\n\nd. ".'In that same vein, "premade linearts" are also permitted as references. They may count for rolls as long as literature accompanies the image when it is submitted to the group.',
                        '**II. Designs**'."\n\n".'All designs and characters that were not designed or created by you return to their original designer(s)/creator(s) upon your departure from the Discord.'."\n\na. ".'All characters will either be released for others to claim, use as an NPC, or deceased should a player leave the Discord at the discretion of the admins.'."\n\nb. ".'This is waived only if they have been purchased with an item from the shop or the design/character has been under your control actively (one roll per month) for at least five in game years.',
                        '**III. Activity**'."\n\n".'Real life comes first, and we are always willing to work with players should they be unable to meet our activity requirements! Please don\'t hesitate to reach out!'."\n\na. ".'Players who miss two activity checks in a row (a timeline of ~6 months) with no notice will have their Herd Leader listed as Away. Players may reinstate their Herd Leader at any time should they return to the game.'."\n\nb. ".'Should a Herd Leader be listed as Away, their herd NPCs will be released. If the Herd Leader is reinstated, their herd may only be reclaimed by natural means - either through weekly requested encounters; randomized encounters; items; etc.'."\n\nc. ".'Herd members that are released and then claimed by other players may not be reclaimed by the returning player unless the herd member becomes available again.',
                        '**IV. In-Game Time**'."\n\n".'Time is the master of our universe, and Rattlesnake Mountain is no different.'."\n\na. ".'All characters will officially age one year every in-game Spring (~every 4 months) for the sake of game continuity. Players may have "unofficial" birth dates for their characters.'."\n\nb. ".'For every year aged, a horse earns +1 Experience.',
                        '**V. Concluding Notes**'."\n\n".'These rules are subject to change at any time. By joining the Rattlesnake Mountain group, you are henceforth agreeing to adhere to these rules at any given time and location pertaining to Rattlesnake Mountain.',
                        '**VI. Disclaimer**'."\n\n".'Rattlesnake Mountain is intended to be a realistic wild horse game that functions similarly to a "Choose Your Own Adventure" novel and Dungeons and Dragons. As a result, character death and possession (herdmates, foals, etc) loss are a very real and intended part of the game. If you cannot handle the potential for your character to lose everything - including their life - then this is not the silly art game for you and we fully encourage you to find another ARPG that suits your preferences.',
                    ],
                    'box2' => [
                        '![](/images/rules-banner-artandlit.png)',
                        '**I. General**'."\n\n".'All responses must be submitted to your character\'s thread in the #story-progression channel. Art and Literature submitted on DeviantArt must be submitted to the group on DeviantArt in order for it to be processed.'."\n\na. ".'Photo backgrounds must be your intellectual property or edited in some way, shape, or form in order for it to count toward a roll.'."\n\nb. ".'Artwork and literature may only be used once in the group. Recolors and reworks are allowed, but will not count toward a roll.'."\n\nc. ".'Collaborations, roleplay, and gift art are, of course, permitted, and often required in player-vs-player situations!'."\n\nd. ".'The bare minimum of stat requirements are 1. As such, literature must be at least 200 words, and art must be at least a headshot.'."\n\ne. ".'All Art and Literature involving your character will be linked on your character\'s DeviantArt upload, along with the staff\'s official stat count. This is in effort to consolidate information and make updates to your character(s) flow much faster!',
                        '**II. Submissions**'."\n\n".'Story submissions should be posted in your herd\'s story thread in #herd-stories with the form filled out.',
                        '# **Rules last updated by @siat-s on April 24th @ 2:20PM EST.**',
                    ],
                ],
                'sort_order' => 3,
            ],
            [
                'slug' => 'lore',
                'title' => 'Lore',
                'description' => 'The history of our world and how it came to be.',
                'hero_title' => 'Lore',
                'hero_description' => 'The history of our world and how it came to be.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/lore-banner-scene.png)',
                        '**PROLOGUE.**'."\n\n".'Lorekeeper: [Lady](https://www.deviantart.com/rattlesnakeadmin/art/RATTLESNAKE-MOUNTAIN-Mare-004-The-Lorekeeper-813634467)',
                        '*They whisper of a time, many hundreds of years ago, when we were not endlessly at war with the desert; when every day was not a struggle to exist and our families were safe from the elements in wooden boxes. Our only trouble in the world of man was when a meal came an hour late, and we never had to worry about wolves getting our children at night. But men fail; their buildings burn and they die, their children failing to take up the mantle of their forefathers. And we were turned loose, either by chance or by fate. I remember, clear as day, my father\'s story - passed down for generation upon generation - of our lineage and how we became free.*',
                        '*He would say, "On the eve of our family\'s Great Sorrow, a terrible storm brewed and fire rained down from the sky. We screamed in terror, calling for the men who had never failed to protect us before. But that night, when we needed them the most, they were as silent as the dead. Wild with fear and betrayal, we broke from our wooden boxes and ran into the fields we had plowed. Foals struggled to keep from being trampled among the panic; in those first hours, it was every horse for themselves.*',
                        '*When the sun rose again, our home of water, grass, and sunlight was covered with ash. Some of us suffered from savage burns on our necks, faces, and backs. Another stallion and I investigated the remains of our smoking home, stupid enough to hope we could return to the life we\'d always known. But bits of our stable still glowed and radiated with heat. Man - or something more powerful - had turned our sanctuary into something ugly and unrecognizable.*',
                        '*And though we felt it deep in our hearts, we were not alone.*',
                        '*The wild horses - those who had met freedom years before - did not take kindly to our arrival. They seemed content to watch at first, perhaps mocking us for what we did not yet know. Even when we did encounter them, the skirmishes were brief and often ended with the wild stallion taking our mares or driving us away. They laughed at us, knowing that we did not belong. Not yet.*',
                        '*But, many long years later, we have learned how to be strong and we have proven that we can survive in the natives\' cruel world. More than that, we have thrived. It is now our your time to take this range in their hooves and shape their lives as they will. I am old and tired, burdened by memories I\'d rather not have, and it is my privilege to stand back and watch what they make."*',
                        '*So, fight your battles, take whatever you can keep, and prove that you can handle anything the world throws at you. But take it from someone who knows... it will be a painful journey.*',
                    ],
                ],
                'sort_order' => 4,
            ],
            [
                'slug' => 'character-handbook',
                'title' => 'Character Handbook',
                'description' => 'A basic guide on character creation.',
                'hero_title' => 'Character Handbook',
                'hero_description' => 'A basic guide on character creation.',
                'images' => [
                    ['name' => 'HotnSpicy1', 'link' => 'https://www.deviantart.com/hotnspicy1', 'path' => '/images/characterhandbook-art-hotnspicy1.png'],
                    ['name' => 'viscella', 'link' => 'https://www.deviantart.com/viscella', 'path' => '/images/characterhandbook-art-viscella.png'],
                    ['name' => 'KarmaArt666', 'link' => 'https://www.deviantart.com/KarmaArt666', 'path' => '/images/characterhandbook-art-KarmaArt666.png'],
                    ['name' => 'bee', 'link' => '', 'path' => '/images/characterhandbook-art-bee.png'],
                    ['name' => 'empiredog', 'link' => 'https://www.deviantart.com/empiredog', 'path' => '/images/characterhandbook-art-empiredog.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/characterhandbook-step-start.png)',
                        'All playable character designs must be done on [our lineart base](https://www.deviantart.com/rattlesnakeadmin/art/RESOURCE-Lineart-Bases-812737644). They will then be uploaded to the Rattlesnake Mountain accounts on [DeviantArt](https://www.deviantart.com/rattlesnakeadmin). This is done in an attempt to keep all basic information about characters in one place for easy access. Of course, once your design is approved and accepted to the group, you are welcome to upload the design to your own account.',
                        "You may choose two existing NPC horses as your character's sire and dam. However, the combination of their genes must be able to create your character's genotype/phenotype. If you need help with this, please let a **@Designer** know in our Discord!",
                    ],
                    'box2' => [
                        '![](/images/characterhandbook-step-coatcolors.png)',
                        'We ask that you keep coat colors realistic. Before your character(s) can be accepted to the group, the design will undergo a realism check--it is possible that an admin may ask you to change your horse\'s design if it does not adhere to our [design rules](https://www.deviantart.com/rattlesnakeadmin/gallery/83931899/design-guide).',
                        "a. If you believe that you are wrongly being asked to change something (we all make mistakes!), please speak with a staff member privately.b. Chestnuts, Bays, Blacks, Duns, and Roans are fairly common among the Rattlesnake Mountain herds and are free to make in either homozygous or heterozygous form. Other Paint markings, Champagnes, Creams, Pearls, Greys, etc are considerably rare and require items (Stones) to add to these genes to genotypes.\n- Rare genes must be applied via Stones or they can be rolled for when breeding.\n- When you join, you are given the opportunity to acquire some of these genes through Stones in your Welcome Package.\n- Players may trade Stones between themselves using the #trading-post channel.\n- Finally, Stones and horses with rare genes may occasionally be found in the wild.\n\nc. Eye color is typically brown, unless there is a coat modifier that calls for a different color (i.e., paint markings and blue eyes.)\n- Eye colors are not restricted and may be any color you wish, including heterochromia, etc.",
                    ],
                    'box3' => [
                        '![](/images/characterhandbook-step-breeds.png)',
                        '*All horses on Rattlesnake Mountain, unless an item has been applied or it has been a random encounter, are considered Mustangs.*',
                        'a. If a "breed-changing" item has been applied, please ensure that your horse is either a hot-blooded or warm-blooded breed.'."\n\n".'b. [Cold-blooded breeds, or Drafts](https://sites.google.com/site/vadhma/draft-breeds-and-definitions), would have a very difficult time on the range and therefore are only be available during special events or through items.'."\n\n".'c. Burros are not permitted as lead characters. That said, they may be added to your herd with a special item or found in the wild as an NPC to claim. They may be designed using our burro lines, which are found within our [import line file](https://www.deviantart.com/rattlesnakeadmin/art/RESOURCE-Lineart-Bases-812737644).',
                    ],
                    'box4' => [
                        '![](/images/characterhandbook-step-sexrole.png)',
                        '*Players are welcome to choose either sex, but Lead Mares and Lead Stallions function in two completely different ways. **Individuals who wish to create LGBTQIA+ characters are encouraged to do so**--the following gender/sex roles are generally the rule and not the exception.*',
                        "a. Lead Mares are the decision-makers, the ones who determine if a mare that the Lead Stallion brings in will stay in the herd or not. They decide where to go, when to go, and how to go about going there.\n- They are the backbone of the herd's social structure, and often will determine whether or not a herd will be successful or not--a strong Lead Mare doesn't let any of her herdmates get stolen, and will occasionally go toe-to-toe with a rude bachelor or inexperienced band stallion.\n\nb. Lead Stallions are, generally speaking, the protectors. They spend their days at the back of or on the outskirts of the herd, relying on the Lead Mare to keep everyone in line.\n- Often, he will play-fight with bachelors and band stallions alike, honing his abilities and keeping himself in shape. They frequently give way to mares of any ranking in the herd, and spend a lot of their time making sure that wayward mares and fillies don't spend too much time in the company of other stallions, especially when they are in estrus.\n\nc. Unless they are joining alongside a herdmember, all newly created horses will be Bachelors or Lone Mares until they claim their first opposite-sex herdmember.",
                    ],
                    'box5' => [
                        '![](/images/characterhandbook-step-age.png)',
                        '*Age is very important to a wild mustang. It determines their place in the world, and how successful they will be in terms of survival and reproduction.*',
                        "a. Mares and Stallions are typically rejected from or leave their birth herd by the age of two. At this point in time, mares will be absorbed into an existing herd and stallions will find or form a bachelor herd.\n\nb. From two until the age of six or seven, stallions will remain bachelors, honing their skills and roughing it. Occasionally, they might find themselves with a mare, but they will quickly lose it to a stronger band stallion. Mares, on the other hand, may have multiple foals, and grow into their herd, creating relationships with the other mares.\n\nc. At around eight, the horse has reached the prime of their life. They are strong and capable, usually finding themselves in the highest ranking socially, and continue to climb that ladder until they become too old.\n\nd. The average mustang's lifespan in the wild is 15-20 years.  Often, stallions will lose their bands and become bachelors again. Mares, however, will remain with her herd as an Elder until her death. As a result, mares usually live and procreate longer than stallions.\n\ne. For more information on our horse's lifespans and the modifiers related to age, please refer to our [Lifespan page](/lifespans).",
                    ],
                    'box6' => [
                        '![](/images/characterhandbook-step-stats.png)',
                        '*All horses are better skilled in one area than another, and Rattlesnake Mountain\'s horses are no different! Our Mustangs\' stats--listed below--are divided into eight different categories. Those familiar with Dungeons and Dragons will recognize our categories--those unfamiliar, [here is an explanation using tomatoes](https://imgur.com/d-d-stats-explained-WzhSq42).*',
                        "Strength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:",
                        "**I. Overview**\n\na. In short, your horse's stats will determine how successful or unsuccessful they will be in Rattlesnake Mountain.\n- For example, a horse with a higher health stat will fair better against injuries than a horse with very little health.\n\nc. Age functions similarly to experience and is a determining factor that will influence your overall stats.\n- For more information on the average lifespan, [see this page](/lifespans).​",
                        "**II. Character Creation**\n\na. All player characters begin with +1 stat in 2 chosen categories excluding Experience and Health (otherwise known as \"proficiencies\").\n- Proficiencies may be any stat category you'd like; ex. +1 Dexterity and +1 Charisma for a dashing rogue!\n\nb. All player characters begin with -2 stats in 2 chosen categories excluding Experience and Health (otherwise known as \"incompetencies\").\n- Incompetencies may be any stat category you'd like and may match a character's personality; ex. -1 Intelligence and -1 Dexterity for a horse who struggles with memory and the art of conversation!\n\nc. Proficiencies and Incompetencies can be leveled as your horse progresses through life. For more information on that, [check out this page](/stats-leveling).",
                        "**III. Experience and Health**\n\na. Experience is gained solely by in-game events or by aging and may, occasionally, counteract deficits in your horse's stats. Regular level-up points may not be allocated into experience.\n- For example, a horse with more experience may pass charisma checks in order to avoid a conflict with another horse, whereas a younger and less experienced horse will not pass those charisma checks.\n\nb. Health is a combination of the Strength and Constitution categories and cannot be directly added to without increasing either Strength or Constitution. Regular level-up points may not be allocated into Health.\n- More health allows your horse to survive more injury events, or will allow them to fight a competitor longer.\n- While it may be tempting to pour all your stats into Strength and Constitution for those reasons, there are plenty of situations in Rattlesnake Mountain where the survival and success of your horse will depend on other stats.\n\nc. If a horse's health percentage hits 0%, they are automatically forced into a \"saving throw\" situation.\n- In these cases, the player must submit a healing image for that horse in order to roll against a d20. If the result of that d20 roll is a 1, the horse is marked as deceased in the game and removed from play unless an appropriate item is applied.\n- This is why herd checkpoints are important: they give you the opportunity to heal herd mates and prepare against the unknown!",
                        "**IV. Earning Stats**\n\nStats are gained for every piece of art or story written of your horse and may be distributed as you see fit. However, just how many stats your horse will gain for an image or story depends on the amount of effort you put into it.\n\na. For a breakdown of how we determine stats per roll, [see this page](/stats-leveling).",
                        '',
                    ],
                    'box7' => [
                        '![](/images/characterhandbook-step-benefits.png)',
                        '*Benefits and Detriments function as a variety of character-defining traits. They may boost or lower stats, or they may provide other benefits, like a higher chance to encounter wildlife.*',
                        "a. A single horse may have up to four Benefits or Detriments and they may be swapped out as you please. Legendary traits cannot be removed from a horse.\n- All player-created (Herd Leader) characters may be created with one of each or two of each category (i.e., two benefits and two detriments; not two benefits and one detriment).\n- Characters may not be created with Rare or Legendary traits.\n- Additionally, Legendary traits can never be purchased with Scorpions or with real money. They must always be either bred for, earned over certain story arcs, or already exist on a claimable horse.\n\nb. Foal traits are rolled at random by moderators unless an item is applied during the breeding or a parent has an applicable breeding trait.\n\nc. Occasionally, game-wide events will have an effect on stats, benefits, and detriments to give them a sense of difficulty. After all, life as a wild horse is not easy!\n- Some story arcs will earn your horse(s) Benefits and Detriments.\n\nd. A comprehensive [list of all benefits and detriments can be found here](https://docs.google.com/spreadsheets/d/1MdMQyHTcjdlbWutxdydxLqXIzEiRysWTnErXblHKHik/edit?gid=0#gid=0).",
                    ],
                ],
                'sort_order' => 5,
            ],
            [
                'slug' => 'stats-leveling',
                'title' => 'Stats & Leveling',
                'description' => 'An arpg for wild horse enthusiasts!',
                'hero_title' => 'Stats & Leveling',
                'hero_description' => 'An arpg for wild horse enthusiasts!',
                'images' => [
                    ['name' => 'zach', 'link' => 'https://www.deviantart.com/snompy', 'path' => '/images/statsleveling-art-zach.png'],
                    ['name' => 'yell', 'link' => 'https://www.deviantart.com/iiyell', 'path' => '/images/statsleveling-art-yell.png'],
                    ['name' => 'KarmaArt666', 'link' => 'https://www.deviantart.com/KarmaArt666', 'path' => '/images/statsleveling-art-KarmaArt666.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/statsleveling-banner-freshcharacters.png)',
                        '*For a reminder on what each stat category does, [here is an explanation using tomatoes](https://imgur.com/d-d-stats-explained-WzhSq42). Rattlesnake Mountain distributes stats in the following categories; **players cannot distribute stats into Experience or Health***.',
                        "Strength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:",
                        "a. All player characters begin with +1 stat in 2 chosen categories excluding Experience and Health (otherwise known as \"proficiencies\").\n- Proficiencies may be any stat category you'd like; ex. +1 Dexterity and +1 Charisma for a dashing rogue!\n\nb. All player characters begin with -2 stats in 2 chosen categories excluding Experience and Health (otherwise known as \"incompetencies\").\n- Incompetencies may be any stat category you'd like and may match a character's personality; ex. -1 Intelligence and -1 Dexterity for a horse who struggles with memory and the art of conversation!",
                    ],
                    'box2' => [
                        '![](/images/statsleveling-banner-levelingstats.png)',
                        "a. Each submission a player makes to their story (or reference for a character), will earn their characters stat points depending on effort. Players must include a breakdown of the stat estimation in each submission (found in the submission form).\n- Stat points (\"attribute points\") may be distributed by the player into Strength, Dexterity, Constitution, Intelligence, Wisdom, or Charisma. Stat points cannot be distributed into Experience or Health.\n\nb. Every 10 stat points allocated into an attribute will level up that category by +1. For example, putting 10 stats into Dexterity will increase a character's Dexterity by an additional +1.\n-  Attribute levels must be confirmed by staff in #requests-and-updates each time an attribute is leveled.\n- Proficiencies are capped at level 6 (for 50 stat points invested) for an additional +6 to their proficiency, excluding any additional bonus from items, etc.\n- Attributes without a bonus or negative score are capped at level 5 for an additional +5 to that stat.\n- Incompetencies are capped at level 4 for an additional +4 to their incompetency, excluding any additional bonus from items, etc.",
                        '# **To submit a level up, please post in #requests-and-updates using the Stat Check form.**',
                    ],
                    'box3' => [
                        '![](/images/statsleveling-banner-artstatcounts.png)',
                        '*Points are counted individually per horse. If a horse is drawn twice in an image, that will increase the points gained over the horse that is drawn only once.*',
                        "**Base Points Per Horse**\n\nHalf & Partial Body: +1\n- Must at least be a headshot, half the front or back of the body, or missing the legs from the end of the shoulder & gaskin down.\n- For art of a horse that is covered by another horse, must lack any evidence that the entire body was drawn prior to putting the other horse in front of it.\n\nFullbody: +2\n- Must be the entire body of the horse. May exclude hooves.\n\nColored: +1 Stat\n- Horse must be colored.\n\nShading: +1 Stat\n- Horse must be shaded.",
                        "**Base Points Per Image/Comic Panel**\n\nBackground: +1 Stat\n- Backgrounds will not be counted if they are simply solid shapes or brushes of shrubs, dirt, etc. Includes photomanipulated backgrounds - however they must be edited in some way!\n\nSimple Animations: +1 Stat\n- Simple Animations include a horse blinking, tail swish, etc.\n\nComplex Animations: +1 Stat\n- Complex Animations are horses walking, trotting, etc or backgrounds with wind, etc.",
                    ],
                    'box4' => [
                        '![](/images/statsleveling-banner-litstatcounts.png)',
                        "Every 200 Words: +1 Stat\n- If a herd member is not included in the story submission, or only featured for one line, the herd member will not receive stats.\n\nEvery 1000 Words: Bonus +1 Stat",
                    ],
                ],
                'sort_order' => 6,
            ],
            [
                'slug' => 'character-upload',
                'title' => 'Character Creation',
                'description' => 'Forms and other ways to create a character.',
                'hero_title' => 'Character Creation',
                'hero_description' => 'Forms and other ways to create a character.',
                'images' => [
                    ['name' => 'bee', 'link' => '', 'path' => '/images/characterupload-art-bee.png'],
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/characterupload-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/characterupload-banner-designchecklist.png)',
                        '*Before submitting your design(s), please be sure to run through this list. Going through this will help you spend less time waiting for your character(s) to be uploaded, and more time playing!*',
                        "- Have you read through our [Rules](/rules), [Character Handbook](/character-handbook), and [Lifespans](/lifespans) pages?\n- Have you checked the saturation of your markings, coats, mane, and tail?\n- Have you colored the small bit of flesh outside the eye?\n- Is your horse on the correct lines? (Or have you specified why they are not?)\n- Have you double-checked your horse's health, stat point allocation, and age?\n- Have you added your character's [Benefits and Detriments](https://docs.google.com/spreadsheets/d/1MdMQyHTcjdlbWutxdydxLqXIzEiRysWTnErXblHKHik/edit?gid=0#gid=0)?\n- Is the story you've given your horse realistic, sensical, and fits in with the world and lore of Rattlesnake Mountain? Remember, horses in real life are not vicious killers or \"edgy\" - in fact, family is super important to them and their development/success through life!\n- Have you checked that you have the needed items or the Scorpions for the genes you desire? You can double-check this in our bot command channel!\n- Is the skin color given to your horse true to their coat? Remember to check our [Design Guide](https://www.deviantart.com/rattlesnakeadmin/gallery/83931899/design-guide) if you aren't sure, or you can always ask for help!\n- Any physical defects your horse has (missing eyes, limbs, etc) will affect their ability to survive on Rattlesnake Mountain. \n- Again, your horse should not be actively evil or immoral; this is a semi-realistic game. As such, please keep in mind that horses are not evil or immoral! Animals are not inherently wicked, mean, etc nor are they like humans - at the end of the day, your character's goal is to survive and procreate.\n- Please keep your backstories realistic, and not overpowered - remember once again that you are playing as a horse! Backstories should not contain evil, abusive, or power-hungry characters unless they are human.",
                        '# *If you\'re sure everything is dandy, go ahead and send your horse off. We can\'t wait to see you in game!*',
                    ],
                    'box2' => [
                        '![](/images/characterupload-banner-creationform.png)',
                        '*Once you have finished your Design, double-check that it follows the requirements in our [Character Handbook](/character-handbook) and the checklist above. Next, upload the design with the below information filled out to a location where we can download a quality (non-pixelated) version from (Google Drive, Sta.sh, Instagram, etc)! Then, please fill out the form posted in our design channels to submit your design.*',
                        '***Please do not edit the type or form in any way and ensure the form is displayed exactly as follows and includes bold or italicized typeface where indicated. Occasionally, when copy/pasted, the form will add extra spaces between lines: please be sure to delete them or copy/paste the form from [this example](https://www.deviantart.com/rattlesnakeadmin/art/RATTLESNAKE-MOUNTAIN-Stallion-B-0021-Umbreon-919282970). When we copy/paste this form into DeviantArt\'s submission box, it should exactly match [this example](https://www.deviantart.com/rattlesnakeadmin/art/RATTLESNAKE-MOUNTAIN-Stallion-B-0021-Umbreon-919282970) in terms of layout. If you need help with text formatting or need a guide on submitting your first character, check out [this video](https://drive.google.com/file/d/1S3VTPAJeEqEllVkQI-Eifcr1fRLiLr1s/view).***',
                        "**ID #:** Keep this blank!\n\n**Name:**\n\n**Gender:** Mare or Stallion\n\n**Age:** Keep in mind that a three-year-old horse cannot be considered a Lead Mare/Stallion!\n\n**Breed:**\n\n**Social Rank:** Bachelor, Band/Lead Stallion, Lead Mare, Herd Mare, etc.\n\n**Phenotype:**\n\n**Genotype:** If you are not sure, an admin will be glad to assign you a genotype that matches your phenotype!\n\n**Personality:** May be as long or as short as you'd like.\n\n**History:** May be as long or as short as you'd like.\n\n**Player:** Your name here.",
                        "**Stat Distribution**\n\nStrength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience: Only gained via in-game events or by aging. This should be 0 if this is your first created character.\n\nHealth: Number determined by combining Strength and Constitution, with an additional percentage modifier based on Lifespan status (formula is as follows (Strength + Constitution) / Modifier Percentage). Ex: 7 + (40% Modifier) = 10HP",
                        "**Benefits:** Choose one or two.\n\nBenefit Name: Description copied from the Google Sheet.",
                        "**Detriments:** Choose one one or two.\n\nDetriment Name: Description copied from the Google Sheet.\n- Either choose from our list or create your own that will be approved at the discretion of our moderators and become a part of the game!",
                        "**Lineart & Background** by @/Cinrillon\n\nDesign by Name here.",
                    ],
                    'box3' => [
                        '![](/images/characterupload-banner-charcreationalts.png)',
                        'If you do not wish to create or design your own character, Rattlesnake Mountain offers a few alternatives.',
                        "**I. Randomized Claimable**\n\na. We will randomly choose from a list of our current claimable Bachelor Stallions and Herd Mares and supply you with three options of each gender. You will then choose one horse, and they will become your main character.\n\n• In order to choose a horse, please post the form found in our #requests-and-updates.",
                        "**II. Adopt a Claimable**\n\na. You may [browse](https://www.deviantart.com/rattlesnakeadmin/gallery?q=claimable) through our available horses and pick out one that you would like to play! Our DeviantArt account houses the most up-to-date information on which horses are available for claim.\n\nb. The horse's personality, history, benefits, and detriments are able to be changed at your discretion. If you find a horse that you would like to adopt, please claim it in #requests-and-updates.",
                        "**III. Ask a Rattlesnake Designer**\n\na. You may ask an official Rattlesnake Designer to design you a horse or design a rolled genotype yourself. An admin will randomly roll you five phenotypes, and you will pick one for the designer to work with you on creating!\n\nb. Please keep in mind, a design may take one-two weeks, depending upon complexity and designer availability.\n\nc. In order to roll your phenotypes, please use the Randomized Claimable form in #requests-and-updates. A Designer may then be found for your chosen genotype in #rattlesnake-studio. All designs should be submitted in #leader-designs when you are finished creating your character.",
                    ],
                ],
                'sort_order' => 7,
            ],
            [
                'slug' => 'shop',
                'title' => 'Shop',
                'description' => 'Come shop, browse and sell.',
                'hero_title' => 'Shop',
                'hero_description' => 'Come shop, browse and sell.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => ['box1' => ['Shop coming soon!']],
                'sort_order' => 8,
            ],
            [
                'slug' => 'wildlife',
                'title' => 'Wildlife',
                'description' => 'Take a walk on the wild side.',
                'hero_title' => 'Wildlife',
                'hero_description' => 'Take a walk on the wild side.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '# [Lifespans](/lifespans)'."\n\n".'# [Story Progression](/story-progression)'."\n\n".'# [Claiming NPCs](/claiming-npcs)'."\n\n".'# [Herd Unity](/herd-unity)'."\n\n".'# [Breeding and Foaling](/breeding-foaling)'."\n\n".'# [Player vs. Player](/player-vs-player)',
                    ],
                ],
                'sort_order' => 9,
            ],
            [
                'slug' => 'lifespans',
                'title' => 'Lifespans',
                'description' => 'An in-general guide to your character\'s life.',
                'hero_title' => 'Lifespans',
                'hero_description' => 'An in-general guide to your character\'s life.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '*Mustangs, known for their tenacity, can live long lives in the wild. However, they are confronted on a daily basis with many trials and tribulations, some of which are dangerous and deadly. Both the very young and very old can find themselves quick meals for a hungry predator and are susceptible to disease. Horses in the prime of their lives are typically stronger, and can face multiple challenges without much damage done to their physical well-being.*',
                    ],
                    'box2' => [
                        '![](/images/lifespans-banner-prenatal.png)',
                        '*Prior to birth, there are a variety of things that can go wrong and result in a miscarriage by the dam or a stillborn. During gestation, it is important to ensure that the dam has plenty of food and clean water--usually this is her responsibility, though there are times when food and water become scarce and relocation becomes the only option.*',
                        "a. While your mare is in gestation, each submission featuring the herd she is with will require a roll on a d20.\n- A roll of 20 or 1 will result in your mare having a complication that you must work to counter as the story progresses.\n- A roll of 10 will result in a stillborn or miscarriage, depending on how long your mare has been gestating for.\n- Items can always be used to ensure a healthy pregnancy!",
                    ],
                    'box3' => [
                        '![](/images/lifespans-banner-newborn.png)',
                        '*Newborn foals are perhaps the most vulnerable creature on the range--it\'s a good thing that their dam is there to protect them!*',
                        "a. Once the foal has been born, an initial health and story check roll will take place.\n- Occasionally, dams may reject their foals, or their milk doesn't flow, or foals don't receive enough colostrum. A roll on a d20 will determine your foal's initial health, which will determine the foal's strength and constitution, which may be distributed as you wish.\n- Rolling a 2 or 15 will force an emergency story event that will require you to work on restoring your foal's health or defending it from predators.\n- Rolling a 1 will force a death saving roll. In the subsequent death saving roll, 1-10 will result in the death of the foal during the birthing process, shortly after birth, or in the weeks that follow due to natural causes (i.e., predation, illness, etc). 11-20 will result in a complicated birth in which the mare and foal will take a percentage away from their health.\n- All other rolls will result in a healthy mother and baby and is considered an uncomplicated birth.\n- As with prenatal foals, items can be used to ensure a healthy newborn and mother!",
                    ],
                    'box4' => [
                        '![](/images/lifespans-banner-weanling.png)',
                        '*Your foal has survived its early childhood and being weaned from its mother (though they may try to drink every once in a while - naughty!) At this stage of the game, your foal has grown to be a yearling and, while their curiosity may still get the better of them, they\'ve learned some of the tricks of the trade.*',
                        'a. Both weanlings and yearlings will gain a +10% modifier to their health stat.',
                    ],
                    'box5' => [
                        '![](/images/lifespans-banner-twoyears.png)',
                        "Two is usually the time that your horse will leave their natal band to create a life of their own. Your horse has grown strong, and with each day they become more and more capable. Small predators, such as coyotes, run from you. If you're in a herd, cougars don't want anything to do with you.",
                        "a. Horses in this age range gain a +20% modifier to your horse's health stat.\n\nb. Horses at this age who attempt claims, steals, or challenges for NPCs receive an automatic 50% debuff to all stats and require two rolls for success where rolls are required.\n- Additionally, should a horse successfully claim a NPC, they will have an increased chance of losing that herd member for each roll.",
                    ],
                    'box6' => [
                        '![](/images/lifespans-banner-fiveyears.png)',
                        '*Your horse is in their prime! Stallions have reached their full adult size and potentially gotten a herd, while mares are beginning to have successful births and, if they have personality and drive for it, are becoming lead mares.*',
                        "a. Stallions will gain a +30% modifier to their health (due to their tendency toward combat and higher metabolic rate).\n\nb. Mares will gain a +40% modifier to their health, however this is decreased to 20% during gestation.",
                    ],
                    'box7' => [
                        '![](/images/lifespans-banner-fifteenyears.png)',
                        '*Wild horses tend not to live past twenty. As they grow older, they once again become susceptible to disease and frailty. Mares may continue to have foals until their death, but it becomes increasingly difficult and dangerous. Stallions usually return to their bachelor roots, retiring from band stallion life.*',
                        "a. All horses--no matter their sex--take an automatic -20% modifier to their health.\n\nb. Older mares that fall pregnant receive a -40% modifier to their health.\n\nc. All horses decrease the herd's travel time by 50%, but have a 50% chance of finding food and water during resource shortage events (droughts, wildfires, etc).",
                    ],
                ],
                'sort_order' => 10,
            ],
            [
                'slug' => 'story-progression',
                'title' => 'Story Progression',
                'description' => 'On adventuring.',
                'hero_title' => 'Story Progression',
                'hero_description' => 'On adventuring.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/storyprog-banner-storyprog.png)',
                        '*Each submission, be it literature or art, continues your herd\'s story. There are three main ways to progress your herd\'s story: story arcs, which are events rolled to start a new journey or during a journey; checkpoints, or the conclusion of a story arc; and game-wide events.*',
                    ],
                    'box2' => [
                        '![](/images/storyprog-banner-storyarcs.png)',
                        '*Story arcs are rolled each time your herd begins to travel to a new location and are continuously rolled along the way. Story arcs are tailored to fit your herd, and can be as simple and easy as finding a herdless horse or picking up a Stone; or as complex and dangerous as happening upon a starving mother cougar and her cub.*',
                        'a. Story arcs are rolled for using the [Story Progression](https://docs.google.com/spreadsheets/d/1noH5IwE7Zx5lBKSJco8bLbonwHDWqoDEdPqzjvJ3HiA/edit?usp=sharing) roller.'."\n\n".'b. Three herd members receive event rolls; all others are eligible for Item rolls, healing rolls, breeding rolls, and individual bond rolls (where applicable).'."\n- ".'Rolls can be affected by Benefits and Detriments.'."\n\n".'c. To continue a story arc, please your next entry to your herd\'s thread with the form found in our #herd-stories channel filled out.',
                    ],
                    'box3' => [
                        '![](/images/storyprog-banner-checkpoints.png)',
                        '*Checkpoints are a save point for your herd\'s story, and allow you to work on the many passive attributes of your herd in a relatively safe location. Checkpoints are immediately available upon reaching a new location.*',
                        " a. Once your herd has reached a checkpoint, they will have an enhanced opportunity to heal, work on herd relationships, birth foals during the correct season, etc.\n- Checkpoints increase all healing, claiming, herd unity building efforts, etc by 20%.\n- Checkpoints decrease the chance of negative-action rolls (foul weather, aggressive horse, human, predator, etc) by 50%. Staying at a Checkpoint for too long, however (~5 Checkpoint submissions), will result in negative-action rolls increasing by 25%.\n\nb. Checkpoints may also be requested during travel, though all effects will be considerably diminished and, by lingering in one place too long, increase the likelihood of encountering danger.\n- To request a Checkpoint during travel, include it in the submission form. These Checkpoints can be important for recovering health or restoring herd unity in emergency situations.\n- Effectiveness of all healing, claiming, herd unity building efforts, etc are decreased by 20%.\n- Travel Checkpoints increase the change of encountering an aggressive horse, human, or predator by 50%.\n\nc. To end the Checkpoint and have a fresh story arc rolled, please post your next response in your herd's thread on our Discord with the appropriate form filled out.\n- In this entry, include the destination your herd is traveling to. This may be changed at any point during the story arcs that follow.\n- You may also include character goals for the new story arc in this submission. This will help our storytellers create your next arc!",
                    ],
                    'box4' => [
                        '![](/images/storyprog-banner-events.png)',
                        '*Game-Wide Events are events that take place in various locations across the map. These events may cause tragedy or triumph, and are often out of the player\'s (and storyteller\'s) control. They are highly dependent on weather and man-made factors, but those who make it through each event will find themselves stronger than before (and well-paid, as this is a game, after all!)​*',
                        "a. No horses are immune to some events, and some may result in the loss of your played character. These events are the main reason we ask that you not play a character that you are attached to in this game--unless you are willing to say goodbye to them unexpectedly.\n- While these events occur once or twice every in-game year and can be dangerous, that does not mean that every event will be negative! Some are good news--like the passing of legislation restricting humans from riding their ATVs in a particular part of the map.\n\nb. Many events are triggered by story arcs. The specifics of which are outlined in the Story Progression roller linked above, but there are some that are triggered by secret means and are intended as surprise or pop-up events!\n\nc. Events will always be announced in the 📢-announcements channel initially. Updates to events will be posted in the #event-announcements channel, and only players with the @Event Alerts role will be tagged.",
                    ],
                ],
                'sort_order' => 11,
            ],
            [
                'slug' => 'claiming-npcs',
                'title' => 'Claiming NPCs',
                'description' => 'Grow your herd.',
                'hero_title' => 'Claiming NPCs',
                'hero_description' => 'Grow your herd.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/claimingnpcs-banner-claiming.png)',
                        '*An NPC is a character that is, in most games, not playable by any individual and exist to bring life to a world or aid in a story-line, be it negatively or positively. In Rattlesnake Mountain, our NPCs exist for that reason, however most are claimable in the sense that they become played characters once they belong to a player\'s herd. Here you will find information on how a NPC horse (mare, stallion, or foal) can be claimed for your herd.*',
                    ],
                    'box2' => [
                        '![](/images/claimingnpcs-banner-process.png)',
                        "a. In order to start claiming an individual mare, stallion, or foal, you must encounter them in your herd's story. This may happen randomly during story progression roles, and may be influenced by both Benefits and Detriments.\n- You may also request to encounter a specific mare, foal, or stallion once per real life week in any submission made during that week; simply include it in the \"Goals\" section of the submission form.\n- An admin will then respond to your artwork or literature, as usual, with a continuation of your herd's story but with the introduction of the mare, stallion, or foal. As stated, this can also occur randomly, and requests do not have to be made to meet an NPC.\n\nb. So-called \"Requested NPCs\" will be considered \"frozen\" and taken out of RNG rotation until they are claimed or one month after the claiming story has begun.\n- NPCs (excluding randomly generated, non-designed encounters) that are RNG'd into your story will not be \"frozen\" or taken out of gameplay, leaving them available for claim by the character who satisfies the NPC first.\n- In the instance that the NPC is claimed before you, but you were on a prompt to claim the NPC, the amount of stats earned from the piece (finished or otherwise) will be doubled for you and any other featured horse. Alternatively, one PvP item will be deposited to your vault for either resale or personal use.\n\nc. Each NPC comes with their own checklist (hidden to you but visible to admins) that must be completed before the NPC will join your herd.\n- This checklist is reliant on the NPC's age, personality, stats, current health, detriments, benefits, and history.\n- Most of these needs are numerical and require a certain amount of interactions, while others are more story and personality related.\n- For example, a young, inexperienced stallion or lead mare will have a difficulty keeping an older and experienced mare in their herd for any length of time. Foals may be easier to claim, but more difficult to keep alive. As such, the process and story will be different for each type of claim attempt.\n\nd. To progress the claiming of an NPC, simply continue the new story route with them, meeting their needs and requirements along the way.\n- While that story may reroute you from your original course, it is always possible to return to your main story after the NPC's claiming story is finished! Think of claiming as a side quest.\n- Once you have successfully claimed an NPC for your herd, it is up to you to flesh out their information: give them a name, improve upon their basic personality and history, etc. e. To make edits to an NPC after they have been claimed, head on over to design-submissions, listing out which items you would like altered.\n- Note that ID #, Sex, Age, Breed, Phenotype, Genotype, Rarity, Design, Distinguishing Marks, etc cannot be changed without an item that allows an overhaul.\n- Randomly generated Genotypes and Phenotypes may be designed by you or a Rattlesnake Designer and may be affected by Stones. To add a gene to a genotype, indicate which Stones you are using when you submit the character's design.\n- To request a Rattlesnake Designer to design your claimable, check out rattlesnake-designs.\n- All designs are subject to admin approval and follow the same process, rules, and regulations stated in both the [Character Handbook](/character-handbook) and [Character Creation](/character-upload) pages unless otherwise stated.",
                    ],
                    'box3' => [
                        '![](/images/claimingnpcs-banner-more.png)',
                        '*Because this is a mostly realistic game, claiming can never be considered permanent. Sometimes, events will separate your herd and it will stay separated. Illness and death are not foreign to wild animals, and even the elements themselves will get too harsh for some horses to survive. There are, of course, some helpful items that will increase your NPC\'s and PC\'s longevity, but they are not always a guarantee and can get quite expensive.*',
                        "a. If an NPC should happen to die during your story, they will be retired from the game completely and moved to the graveyard.\n- NPCs can only be retired from gameplay if they have died or if they have an item used to retire them.\n- Once retired or deceased, an NPC cannot be brought back to life.\n\nb. If your herd leader falls inactive (missing two activity checks (~6 months) without notice or leaving the Discord), all NPC's under them will be released and considered claimable again.\n- In some instances--that is, another herd is close by, the leaders of the other herd will have the opportunity to easily claim those newly released herd members.\n- This is done via thread on our Discord. We recommend that if you have mentions turned off on our Discord, to turn them on, or check for updates each week.\n\nc. Claiming has an immediate and direct effect on Herd Unity (not all horses are best friends right away!) For more on Herd Unity and the effect it has on your herd, check out our [Herd Unity](/herd-unity) page.",
                    ],
                ],
                'sort_order' => 12,
            ],
            [
                'slug' => 'herd-unity',
                'title' => 'Herd Unity',
                'description' => 'A measure of how close your herd is.',
                'hero_title' => 'Herd Unity',
                'hero_description' => 'A measure of how close your herd is.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/herdunity-banner-unity.png)',
                        '*Herd Unity is the deciding factor when it comes to keeping your herd together. Individual health and experience are useful when it comes to defending your herd from threats, but if your Herd Unity is low, your herd is more easily scattered or predated upon. Therefore, it\'s always a good idea to try and keep your Herd Unity high. Herd Unity has a maximum of 100%, which indicates that the herd is very close-knit; and a minimum of 0%, which indicates the opposite.*',
                    ],
                    'box2' => [
                        '![](/images/herdunity-banner-decreases.png)',
                        "a. New herd members always have a detrimental effect on Herd Unity.\n- Herd Unity is decreased each time a new character joins your herd via Individual Bonds (that is, the adoration felt toward your Herd Leader). You may work to increase it after the fact throughout your herd's Story Progression, noting that it is easier to do so during Checkpoints.\n- The amount of Unity lost per claim is dependent on the newcomer's personality, history, social rank, detriments, benefits, etc. For example, an older mare who was stolen from a stallion she has spent many years with will decrease your herd's Unity much more than a two-year-old filly who just left her natal herd--in fact, it's highly possible that older mare will try leave at the earliest opportunity!\n- This decrease is rolled in the first submission with the new horse in your herd.\n\nb. Story arcs with negative interactions decrease Herd Unity via Individual Bonds.\n- This includes, but is not limited to: failing to keep the herd together during events, negative human interactions, injury from predators, steals, and challenges.\n- Once a horse's Individual Bond has reached 100%, it will be harder to lose that bond. Individual Bonds that have reached 100% can only be decreased during game-wide events or story arcs in which something drastic negatively affects the horse.\n\nc. Conflicting personalities, some detriments, items, social status, etc may decrease Herd Unity via Individual Bonds.\n- Keep an eye on those individual bonds! While some horses won't ever be friends, they can at least develop working relationships... sometimes. Otherwise, horses with conflicting personalities just won't help with herd harmony.\n- Detriments and items may have an impact on Herd Unity. To apply detriments or items to your herd, head on over to the #requests-and-updates channel in our Discord.\n- It's important to keep in mind your herd members' social status. Two mares who have the Lead Mare social status will often be in a struggle for dominance with one another until one gives in or, more often than not, leaves the herd.",
                    ],
                    'box3' => [
                        '![](/images/herdunity-banner-increase.png)',
                        'a. Herd Unity is naturally increased or decreased over time, however increases are buffed during Checkpoints and debuffed during Travel Checkpoints.'."\n- ".'To read up on the two kinds of Checkpoints, check out our page on [Story Progression](/story-progression).'."\n\n".'b. Herd Unity may also be increased through positive events in story arcs.'."\n- ".'This includes, but is not limited to: keeping the herd together during events; keeping your herd safe from predators and humans; successfully defending your herd during steals and challenges; and positive herd member interactions'."\n\n".'c. Personalities that mesh well, some benefits, items, social status, etc.'."\n- ".'Some horses are just more easy-going or willing to make and keep friends. Horses that get along with one another tend to stay together.'."\n- ".'Some benefits and items will increase Herd Unity. To apply benefits or items to your herd, post in our requests-and-updates channel in our Discord.'."\n- ".'Having little overlap in social status is hugely beneficial for stable Herd Unity. Several Low Ranking mares are more ideal to a tightly-knit herd than multiple Lead Mare status mares.',
                    ],
                ],
                'sort_order' => 13,
            ],
            [
                'slug' => 'breeding-foaling',
                'title' => 'Breeding & Foaling',
                'description' => 'Create new life.',
                'hero_title' => 'Breeding & Foaling',
                'hero_description' => 'Create new life.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/breedingfoaling-banner-breeding.png)',
                        '*Passing on their genes and ensuring their lineage survives is the end goal of most animals in the wild. Here you will find information on how breeding and foaling works on Rattlesnake Mountain.*',
                    ],
                    'box2' => [
                        '![](/images/breedingfoaling-banner-notes.png)',
                        "a. Breeding and foaling may only occur at specific places and times.\n- Mares may come into heat at any time after they turn two years of age. Heat or Estrus is a randomly rolled status during the Breeding and Foaling season and lasts until the mare conceives or two real life weeks have passed.\n- The Breeding and Foaling season lasts from the end of Winter to the last few weeks of Autumn. Dates are indicated on our calendar, which can be found on our [home page](/).\n- In real life, the gestational period for foals is ~11-12 months. On Rattlesnake Mountain, mares may start giving birth the following Spring, the due date provided at the time of breeding. Players may choose to delay the due date to ensure their foal arrives at their preferred time.\n- Your herd must be at a checkpoint to submit a breeding. This means that your mares have a chance of being courted by other stallions during regular story progression.",
                    ],
                    'box3' => [
                        '![](/images/breedingfoaling-banner-submit.png)',
                        "a. Prior to submitting a breeding request, please take note of the following.\n- Breeding attempts must be accompanied by a non-graphic or descriptive image or short story - keep it PG or a fade-to-black - during a Checkpoint.\n- Breeding may only be attempted twice per estrus cycle.\n- Please keep in mind that breedings are not always 100% effective, nor do they guarantee a live foal that will live a long life or a healthy mare. Predators have to eat, too, and as a prey animal, the life of a wild horse is nothing if not dangerous.\n\nb. Breeding requests should be posted in the #breeding-rolls channel with the correct form filled out.",
                    ],
                    'box4' => [
                        '![](/images/breedingfoaling-banner-results.png)',
                        "*After you have submitted your breeding request, a @Moderator will respond in your character's thread with the following:*",
                        "**Sex:** To be chosen by you unless the gender ratios in the entire game are skewed unrealistically. That is, if there are more stallions than mares.\n\n**Breed:** Should be Mustang, unless another breed has been introduced to your foal's bloodline.\n\n**Genotype & Phenotype:** You'll receive two to work with, unless the mare has twins or you opt for a reroll, in which case you may choose from all rolled genotypes and phenotypes. Other mutations will be indicated here as well.\n\n**Markings:** Will be left up to you, although some markings may be randomized.\n\n**Health & Stats:** This will denote the health of the mare and the foal at the time of conception and during birth. Mares will receive rolls throughout pregnancy as their stories continue.\n\n**Benefits & Detriments:** Some are inheritable and some are not. Breeding is the only way to achieve Legendary traits.",
                    ],
                    'box5' => [
                        '![](/images/breedingfoaling-banner-designs.png)',
                        '*Received a healthy (or unhealthy but alive) foal? Wonderful! The next step is to design your foal using our official line art. Simply download [the line art](https://www.deviantart.com/rattlesnakeadmin/art/RESOURCE-Lineart-Bases-812737644), open the file in your favorite editor, and get to work on that foal design! If you need help or would like a @Designer to design your foal for you, please do not hesitate to ask a @Moderator or @Designer, respectively! Remember, while Google can be a great asset, it is not always 100% accurate in depicting correct patterns or base coats. Alternatively, you are welcome to check out [our design guide](https://www.deviantart.com/rattlesnakeadmin/gallery/83931899/design-guide).*',
                        '**Once you or a @Designer have designed your foal following the genotype given to you, please upload that file somewhere and submit that file for review in the #foal-designs channel in our Discord. Please ensure the following form is filled out. Do not change text or add any text effects.**',
                        "**ID #:** This is supplied to you in your foal results.\n\n**Gender:** Mare or Stallion, if the option was provided to you\n\n**Age:** Newborn\n\n**Breed:**\n\n**Social Rank:** Newborn\n\n**Phenotype:** Choose one of the phenotypes & genotypes that were provided to you in your foal results.\n\n**Genotype:**\n\n**Personality:** May be as long or as short as you'd like.\n\n**History:** May be as long or as short as you'd like.\n\n**Herd Leader:** Link your herd leader's DeviantArt upload here.",
                        "**Stat Distribution, provided to you with your foal results.**\n\nStrength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:",
                        "**Benefits:** Provided to you with your foal results. If you have any in your Vault, you may add them here.\n\nBenefit Name: Description copied from the Google Sheet.",
                        "**Detriments:** Provided to you with your foal results. If you have any in your Vault, you may add them here.\n\nDetriment Name: Description copied from the Google Sheet.",
                        "**Lineart & Background** by @/Cinrillon\n\nDesign by Name here.",
                        'Link to your foal results.',
                    ],
                ],
                'sort_order' => 14,
            ],
            [
                'slug' => 'player-vs-player',
                'title' => 'Player vs. Player',
                'description' => 'Fight for what you want.',
                'hero_title' => 'Player vs. Player',
                'hero_description' => 'Fight for what you want.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '![](/images/pvp-banner-events.png)',
                        'Player-versus-Player--or PvP--are events that are generated and created by you! It is up to you whether your character will become a dominant force in Rattlesnake Mountain or be a wallflower. NPC steals and challenges can only get you so much renown, after all!',
                        'Note: PvP should be initiated for the sake of fun and character development; it should never used as a way to attack another player--any instance of this would be breaking our [number one rule](/rules)!',
                    ],
                    'box2' => [
                        '![](/images/pvp-banner-steals.png)',
                        '*Steals are quick, one response attempts to acquire up to two mares (and their below yearling-age foals) from another herd.*',
                        'a. In order to initiate a steal, either draw or write your attempt to coerce or drive the mare(s) away from their herd and submit it to your character thread in our Discord with the following form filled out:',
                        "Opponent: Link your opponent's DeviantArt upload here.\n\nTarget(s): Link all targeted horses' DeviantArt uploads here, including any foals that are below yearling age.",
                        "b. A @Moderator will then tag the stallion or mare's player(s) in a reply to your steal post.\n- The opponent will then have one week to make a response in the form of art or literature, else the steal will default to the initiator.\n\nc. Once the opponent has responded, the stats, benefits, detriments, and items (if any are used) of all horses involved (the initiator, the opponent(s), and the potential prize(s) are weighed against each other by a PvP admin.\n- A little bit of luck will factor into these steals, so even if your opponent outweighs you stat-wise, there is still a chance of victory. While steals are typically less risky, there is always a chance for injury--this will be rolled for in your victory rolls.\n\nd. It is possible to gain items, scorpions and stats through steals.\n- Each piece submitted will have a 25% chance of finding one of these things, and their quantity and/or rarity will be determined through an extra roll.",
                    ],
                    'box3' => [
                        '![](/images/pvp-banner-challenges.png)',
                        '*Challenges, as opposed to steals, take a little bit more time and effort--but have a lot more risk and a lot more reward.*',
                        "a. Outright fights between stallions or even mares are quite rare in the wild, as they can be very detrimental to the health of both fighters.\n- As such, each response in a challenge has a 50% chance of resulting in an injury.\n- Mares, as they typically do not fight in this manner, will receive a 75% chance of resulting in injury,\n- Each image will result in a roll on a d100, with a 1 resulting in the accidental death of the horse depicted.\n\nb. Challenges can be issued for more than two mares in a herd or a horse's entire herd.\n- In order to initiate a challenge, either draw or write your horse posturing up against their opponent and post it in your character's thread on our Discord with the following form:",
                        "Opponent: Link your opponent's DeviantArt upload here.\n\nTarget(s): Link all targeted horses' DeviantArt uploads here, including any foals that are below yearling age.",
                        "b. A @Moderator will then tag the stallion or mare's player(s) in a reply to your challenge post.\n- The opponent will then have one week to make a response in the form of art or literature, else the challenge will default to the initiator. Their response must be posted in the initiator's character thread.\n\nc. The initiator then must depict the fight in either art or literature and submit it to their character thread.\n- The initiator has two weeks to make this response.\n\nd. The opponent then must show their side of the fight in either art or literature.\n- The opponent has two weeks to make this response.\n\ne. All challenges have one month to finish completely, else they will be called early.\n\nf. Once the challenge has finished, the stats, benefits, detriments, and items (if any are used) of all horses involved (the initiator, the opponent(s), and the potential prize(s) are weighed against each other by a PvP admin.\n- Luck will play a factor in these fights.\n\ng. Once the admin has the default results, they will be posted in a Google form and a vote will be called for the entire playerbase to take part in.\n- The playerbase must always supply a reason why they're choosing a specific horse as the victor; this can be done anonymously, but this feedback will be posted on the Google form for the challenge artists to see.",
                    ],
                    'box4' => [
                        '![](/images/pvp-banner-spars.png)',
                        '*Spars are practice challenges that have little to no risk outside of injury. Stallions--especially bachelors--spar with each other quite frequently to hone their battle skills and keep themselves in shape. A stallion with a lot of spars under his belt will fair better against injury in challenges and steals.*',
                        "a. Spars are an agreed-upon PvP setting, and may have their own predetermined outcome.\n- If they do not have a predetermined winner, a @Moderator will weigh them in the same manner as steals.\n- In order to initiate a spar, submit an image or literature with the following form filled out to your character's thread.",
                        "Opponent: Link your opponent's DeviantArt upload here.",
                        "\n- Once a spar has finished, it is your responsibility to tag a @Moderator to roll the results if the results have not been decided already.",
                    ],
                    'box5' => [
                        '![](/images/pvp-banner-trades.png)',
                        '*A much more peaceful method of getting what your character wants, trading mares or of-age (usually older than a year) is unsanctioned by the admin team. All we ask is that you don\'t harass someone for their mare(s) and you try to keep things fair. In order to initiate a trade, simply post in the #trading-post channel of our Discord. We recommend creating a story (the mare(s) wandered away from their old herd, their herd stallion encourage it, etc) to go along with this trade, of course!*',
                    ],
                ],
                'sort_order' => 15,
            ],
            [
                'slug' => 'contact-us',
                'title' => 'Contact Us',
                'description' => 'Feel free to get in touch.',
                'hero_title' => 'Contact Us',
                'hero_description' => 'Feel free to get in touch.',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => ['box1' => ['# [Send us an email!](mailto:abaturestudio@gmail.com?subject=Rattlesnake%20Mountain%20Contact%20Us%20Inquiry)']],
                'sort_order' => 16,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'description' => 'How we handle your data',
                'hero_title' => 'Privacy Policy',
                'hero_description' => 'How we handle your data',
                'images' => [
                    ['name' => 'Nikkayla', 'link' => 'https://www.deviantart.com/Nikkayla', 'path' => '/images/lore-art-Nikkayla.png'],
                ],
                'content' => [
                    'box1' => [
                        '# Data We Collect',
                        'We collect the following information:',
                        '- Name and email address (for account creation)',
                        '- Character images you upload',
                        '- Herd and horse data you create',
                        '- Session cookies (essential for site functionality)',
                    ],
                    'box2' => [
                        '# Why We Collect It',
                        'We collect this data to:',
                        '- Provide you with account functionality',
                        '- Store your game data (herds, horses, character images)',
                        '- Maintain your session while using the site',
                    ],
                    'box3' => [
                        '# Data Retention',
                        '- Account data: Retained until you delete your account',
                        '- Character images: Soft-deleted items are permanently removed after 30 days',
                        '- Logs: Retained for 14 days (warning level and above only)',
                    ],
                    'box4' => [
                        '# Data Sharing',
                        'We do not share your personal data with third parties for marketing purposes.',
                        'We use Mailgun (a third-party email service) to send transactional emails such as account verification and password resets. Mailgun processes your email address only for the purpose of delivering these emails. You can review Mailgun\'s privacy policy at [mailgun.com/legal/privacy-policy](https://www.mailgun.com/legal/privacy-policy).',
                    ],
                    'box5' => [
                        '# Your Rights',
                        'You have the right to:',
                        '- Access your data (view your account settings)',
                        '- Delete your data (delete your account from settings)',
                        '- Request a copy of your data (contact us)',
                    ],
                    'box6' => [
                        '# Contact',
                        'For privacy-related inquiries, contact us at:',
                        '[abaturestudio@gmail.com](mailto:abaturestudio@gmail.com?subject=Privacy%20Inquiry)',
                    ],
                ],
                'sort_order' => 17,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::create($page);
        }
    }
}
