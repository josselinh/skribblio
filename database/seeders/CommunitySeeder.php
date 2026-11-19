<?php

namespace Database\Seeders;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a small community of users sharing themed groups and collections, so that the "Explorer"
 * page has something to discover: groups of various sizes, update dates spread over a few months,
 * favorites unevenly spread so that the popularity sort is meaningful, and contributions: words and
 * groups added by other users, including to items closed since (the contributions are kept).
 *
 * It can run on top of existing accounts, and again: it first deletes the community users, which deletes
 * everything they created. Every community user's password is "password".
 */
class CommunitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Community users, by key.
     *
     * @var array<string, string>
     */
    private const array USERS = [
        'camille' => 'Camille Martin',
        'hugo' => 'Hugo Bernard',
        'lea' => 'Léa Dubois',
        'nathan' => 'Nathan Moreau',
        'ines' => 'Inès Laurent',
        'theo' => 'Théo Garnier',
    ];

    /**
     * Groups: author key, visibility, days since last update, number of users who favorited it, words.
     *
     * @var array<string, array{author: string, visibility: Visibility, days_ago: int, favorites: int, words: list<string>}>
     */
    private const array GROUPS = [
        'Animaux de la ferme' => ['author' => 'camille', 'visibility' => Visibility::Public, 'days_ago' => 2, 'favorites' => 5, 'words' => ['vache', 'cochon', 'poule', 'mouton', 'chèvre', 'cheval', 'âne', 'canard', 'oie', 'dindon', 'lapin', 'coq', 'veau', 'agneau', 'poussin', 'taureau']],
        'Animaux sauvages' => ['author' => 'camille', 'visibility' => Visibility::Public, 'days_ago' => 20, 'favorites' => 4, 'words' => ['lion', 'tigre', 'éléphant', 'girafe', 'zèbre', 'hippopotame', 'rhinocéros', 'gorille', 'crocodile', 'guépard', 'panthère', 'loup', 'ours', 'renard', 'kangourou', 'koala', 'panda', 'chameau', 'autruche', 'flamant rose', 'pingouin', 'phoque', 'baleine', 'requin', 'dauphin', 'pieuvre']],
        'Fruits et légumes' => ['author' => 'hugo', 'visibility' => Visibility::Public, 'days_ago' => 5, 'favorites' => 3, 'words' => ['pomme', 'banane', 'fraise', 'cerise', 'ananas', 'pastèque', 'citron', 'orange', 'poire', 'raisin', 'kiwi', 'mangue', 'carotte', 'tomate', 'poireau', 'courgette', 'aubergine', 'brocoli', 'champignon', 'pomme de terre', 'oignon', 'radis', 'citrouille', 'avocat']],
        'Desserts' => ['author' => 'hugo', 'visibility' => Visibility::Public, 'days_ago' => 45, 'favorites' => 1, 'words' => ['crêpe', 'gaufre', 'éclair', 'macaron', 'tarte', 'mousse au chocolat', 'crème brûlée', 'glace', 'cookie', 'donut', 'cupcake', 'tiramisu']],
        'Plats du monde' => ['author' => 'hugo', 'visibility' => Visibility::Public, 'days_ago' => 12, 'favorites' => 2, 'words' => ['pizza', 'sushi', 'tacos', 'hamburger', 'couscous', 'paella', 'raclette', 'fondue', 'lasagnes', 'ramen', 'kebab', 'croque-monsieur', 'hot-dog', 'quiche', 'ratatouille', 'cassoulet', 'curry', 'nems']],
        'Métiers' => ['author' => 'lea', 'visibility' => Visibility::Public, 'days_ago' => 1, 'favorites' => 5, 'words' => ['pompier', 'policier', 'médecin', 'infirmière', 'boulanger', 'facteur', 'astronaute', 'pilote', 'cuisinier', 'professeur', 'vétérinaire', 'plombier', 'électricien', 'jardinier', 'coiffeur', 'dentiste', 'pirate', 'magicien', 'clown', 'détective', 'agriculteur', 'peintre', 'photographe', 'chanteur', 'danseur', 'architecte', 'juge', 'serveur']],
        'Sports' => ['author' => 'lea', 'visibility' => Visibility::Public, 'days_ago' => 30, 'favorites' => 3, 'words' => ['football', 'tennis', 'basket', 'rugby', 'natation', 'ski', 'judo', 'escalade', 'surf', 'vélo', 'golf', 'boxe', 'handball', 'pétanque', 'équitation', 'patinage', 'escrime', 'badminton', 'bowling', 'karaté']],
        'Instruments de musique' => ['author' => 'lea', 'visibility' => Visibility::Public, 'days_ago' => 60, 'favorites' => 0, 'words' => ['guitare', 'piano', 'violon', 'batterie', 'trompette', 'flûte', 'harpe', 'accordéon', 'saxophone', 'tambour', 'xylophone', 'harmonica', 'violoncelle', 'triangle']],
        'Objets de la maison' => ['author' => 'nathan', 'visibility' => Visibility::Public, 'days_ago' => 8, 'favorites' => 2, 'words' => ['canapé', 'lampe', 'frigo', 'aspirateur', 'télévision', 'horloge', 'miroir', 'parapluie', 'brosse à dents', 'oreiller', 'fourchette', 'casserole', 'grille-pain', 'bougie', 'tapis', 'étagère', 'radiateur', 'balai', 'savon', 'serviette', 'cintre', 'robinet', 'baignoire', 'tiroir', 'escalier', 'fenêtre', 'clé', 'poubelle', 'théière', 'ventilateur']],
        'Moyens de transport' => ['author' => 'nathan', 'visibility' => Visibility::Public, 'days_ago' => 90, 'favorites' => 1, 'words' => ['voiture', 'avion', 'train', 'bateau', 'vélo', 'moto', 'bus', 'métro', 'hélicoptère', 'fusée', 'trottinette', 'tracteur', 'camion', 'sous-marin', 'montgolfière', 'tramway', 'skateboard', 'taxi']],
        'Pays et monuments' => ['author' => 'nathan', 'visibility' => Visibility::Public, 'days_ago' => 15, 'favorites' => 4, 'words' => ['tour Eiffel', 'pyramide', 'Big Ben', 'statue de la Liberté', 'Colisée', 'muraille de Chine', 'Taj Mahal', 'tour de Pise', 'arc de Triomphe', 'Stonehenge', 'Sphinx', 'Mont-Saint-Michel', 'Sagrada Família', 'Christ Rédempteur']],
        'Mes idées en vrac' => ['author' => 'nathan', 'visibility' => Visibility::Private, 'days_ago' => 3, 'favorites' => 0, 'words' => ['licorne', 'robot', 'château de sable', 'arc-en-ciel', 'volcan']],
        'Super-pouvoirs' => ['author' => 'ines', 'visibility' => Visibility::Public, 'days_ago' => 4, 'favorites' => 2, 'words' => ['invisibilité', 'téléportation', 'voler', 'super force', 'lire dans les pensées', 'voyage dans le temps', 'respirer sous l\'eau', 'rétrécir', 'contrôler le feu', 'vision laser']],
        'Contes et légendes' => ['author' => 'ines', 'visibility' => Visibility::Public, 'days_ago' => 25, 'favorites' => 5, 'words' => ['dragon', 'sirène', 'licorne', 'sorcière', 'fée', 'ogre', 'chevalier', 'princesse', 'château', 'baguette magique', 'citrouille', 'pantoufle de verre', 'loup-garou', 'vampire', 'fantôme', 'troll', 'lutin', 'génie', 'tapis volant', 'potion', 'grimoire', 'couronne']],
        'Météo et nature' => ['author' => 'ines', 'visibility' => Visibility::Public, 'days_ago' => 50, 'favorites' => 1, 'words' => ['soleil', 'nuage', 'pluie', 'orage', 'éclair', 'neige', 'arc-en-ciel', 'tornade', 'brouillard', 'grêle', 'montagne', 'cascade', 'volcan', 'désert', 'forêt', 'rivière', 'plage', 'île', 'grotte', 'lune', 'étoile']],
        'Expressions françaises' => ['author' => 'theo', 'visibility' => Visibility::Public, 'days_ago' => 7, 'favorites' => 3, 'words' => ['coup de foudre', 'tomber dans les pommes', 'avoir le cafard', 'poser un lapin', 'coûter un bras', 'avoir un chat dans la gorge', 'mettre les pieds dans le plat', 'tirer les vers du nez', 'avoir la pêche', 'casser les pieds', 'en faire un fromage', 'raconter des salades']],
        'Jeux vidéo' => ['author' => 'theo', 'visibility' => Visibility::Public, 'days_ago' => 35, 'favorites' => 2, 'words' => ['manette', 'console', 'joystick', 'pixel', 'boss', 'vie bonus', 'game over', 'sauvegarde', 'niveau', 'casque VR', 'borne d\'arcade', 'champignon', 'épée', 'trésor', 'checkpoint']],
        'Soirée entre amis' => ['author' => 'theo', 'visibility' => Visibility::Private, 'days_ago' => 6, 'favorites' => 0, 'words' => ['karaoké', 'pizza', 'jeu de société', 'canapé', 'pop-corn', 'film d\'horreur', 'chips', 'guitare']],
        'Tout petits mots' => ['author' => 'camille', 'visibility' => Visibility::Public, 'days_ago' => 120, 'favorites' => 0, 'words' => ['œuf', 'nez', 'lit', 'feu', 'clé', 'sac', 'bol', 'roi']],
    ];

    /**
     * Groups closed to contributions (the others are open).
     *
     * @var list<string>
     */
    private const array CLOSED_GROUPS = ['Desserts', 'Instruments de musique', 'Expressions françaises', 'Mes idées en vrac'];

    /**
     * Words added by other users, by group then contributor key. Some groups were closed since.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const array WORD_CONTRIBUTIONS = [
        'Animaux sauvages' => ['hugo' => ['paresseux', 'lama'], 'theo' => ['ornithorynque']],
        'Métiers' => ['camille' => ['boucher', 'fleuriste'], 'ines' => ['sage-femme']],
        'Contes et légendes' => ['lea' => ['centaure', 'gargouille']],
        'Jeux vidéo' => ['nathan' => ['manette sans fil', 'speedrun']],
        'Desserts' => ['lea' => ['île flottante']],
        'Expressions françaises' => ['ines' => ['avoir la tête dans les nuages', 'se faire rouler dans la farine']],
    ];

    /**
     * Collections: author key, visibility, days since last update, number of users who favorited it, group names.
     *
     * @var array<string, array{author: string, visibility: Visibility, days_ago: int, favorites: int, groups: list<string>}>
     */
    private const array COLLECTIONS = [
        'Partie en famille' => ['author' => 'camille', 'visibility' => Visibility::Public, 'days_ago' => 3, 'favorites' => 5, 'groups' => ['Animaux de la ferme', 'Animaux sauvages', 'Fruits et légumes', 'Métiers', 'Moyens de transport']],
        'Spécial gourmands' => ['author' => 'hugo', 'visibility' => Visibility::Public, 'days_ago' => 10, 'favorites' => 2, 'groups' => ['Fruits et légumes', 'Desserts', 'Plats du monde']],
        'Imaginaire' => ['author' => 'ines', 'visibility' => Visibility::Public, 'days_ago' => 18, 'favorites' => 3, 'groups' => ['Contes et légendes', 'Super-pouvoirs', 'Jeux vidéo']],
        'Tour du monde' => ['author' => 'nathan', 'visibility' => Visibility::Public, 'days_ago' => 40, 'favorites' => 1, 'groups' => ['Pays et monuments', 'Plats du monde', 'Moyens de transport', 'Météo et nature']],
        'Grande soirée' => ['author' => 'theo', 'visibility' => Visibility::Public, 'days_ago' => 2, 'favorites' => 4, 'groups' => ['Expressions françaises', 'Jeux vidéo', 'Sports', 'Instruments de musique', 'Objets de la maison', 'Métiers']],
        'Brouillons' => ['author' => 'nathan', 'visibility' => Visibility::Private, 'days_ago' => 1, 'favorites' => 0, 'groups' => ['Mes idées en vrac', 'Objets de la maison']],
    ];

    /**
     * Collections open to contributions (the others are closed).
     *
     * @var list<string>
     */
    private const array OPEN_COLLECTIONS = ['Partie en famille', 'Grande soirée'];

    /**
     * Groups added by other users, by collection then contributor key. "Mes idées en vrac" is private: the
     * other viewers of the collection are told a group is hidden. "Imaginaire" was closed since.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const array GROUP_CONTRIBUTIONS = [
        'Partie en famille' => ['lea' => ['Sports'], 'ines' => ['Super-pouvoirs']],
        'Grande soirée' => ['nathan' => ['Mes idées en vrac', 'Pays et monuments']],
        'Imaginaire' => ['camille' => ['Animaux sauvages']],
    ];

    public function run(): void
    {
        User::query()->whereIn('email', array_map(fn (string $key): string => "{$key}@example.com", array_keys(self::USERS)))->delete();

        $users = collect(self::USERS)->map(fn (string $name, string $key): User => User::factory()->create([
            'name' => $name,
            'email' => "{$key}@example.com",
        ]));

        $wordGroups = collect(self::GROUPS)->map(function (array $group, string $name) use ($users): WordGroup {
            $author = $users[$group['author']];
            $wordGroup = WordGroup::factory()->createdBy($author)->create([
                'name' => $name,
                'visibility' => $group['visibility'],
                'accepts_contributions' => ! in_array($name, self::CLOSED_GROUPS, true),
            ]);
            foreach ($group['words'] as $word) {
                $wordGroup->words()->make(['name' => $word])->creator()->associate($author)->save();
            }
            foreach (self::WORD_CONTRIBUTIONS[$name] ?? [] as $contributorKey => $words) {
                foreach ($words as $word) {
                    $wordGroup->words()->make(['name' => $word])->creator()->associate($users[$contributorKey])->save();
                }
            }
            $this->favoriteBy($wordGroup, $users, $author, $group['favorites']);
            $wordGroup->newQuery()->whereKey($wordGroup->getKey())->update(['updated_at' => now()->subDays($group['days_ago'])]);

            return $wordGroup;
        });

        foreach (self::COLLECTIONS as $name => $collection) {
            $author = $users[$collection['author']];
            $groupCollection = GroupCollection::factory()->createdBy($author)
                ->hasAttached($wordGroups->only($collection['groups'])->values(), ['added_by' => $author->id])
                ->create([
                    'name' => $name,
                    'visibility' => $collection['visibility'],
                    'accepts_contributions' => in_array($name, self::OPEN_COLLECTIONS, true),
                ]);
            foreach (self::GROUP_CONTRIBUTIONS[$name] ?? [] as $contributorKey => $groupNames) {
                $groupCollection->wordGroups()->attach(
                    $wordGroups->only($groupNames)->values(),
                    ['added_by' => $users[$contributorKey]->id],
                );
            }
            $this->favoriteBy($groupCollection, $users, $author, $collection['favorites']);
            $groupCollection->newQuery()->whereKey($groupCollection->getKey())->update(['updated_at' => now()->subDays($collection['days_ago'])]);
        }
    }

    /**
     * Has the given number of community users, other than the author, favorite the item.
     *
     * @param  Collection<string, User>  $users
     */
    private function favoriteBy(WordGroup|GroupCollection $item, Collection $users, User $author, int $count): void
    {
        $item->favoritedBy()->attach(
            $users->reject(fn (User $user): bool => $user->is($author))->take($count)->pluck('id'),
        );
    }
}
