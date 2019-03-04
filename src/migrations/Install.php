<?php
namespace fruitstudios\linkit\migrations;

use fruitstudios\linkit\Linkit;
use fruitstudios\linkit\fields\LinkitField;
use fruitstudios\linkit\models\Email;
use fruitstudios\linkit\models\Phone;
use fruitstudios\linkit\models\Url;
use fruitstudios\linkit\models\Entry;
use fruitstudios\linkit\models\Category;
use fruitstudios\linkit\models\Asset;
use fruitstudios\linkit\models\Product;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use craft\services\Fields;
use craft\services\Plugins;

class Install extends Migration
{
    public function safeUp()
    {
        $this->upgradeFromCraft2();
        return true;
    }

    protected function upgradeFromCraft2()
    {
        // Locate and remove old linkit
        $row = (new \craft\db\Query())
            ->select(['id'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['fruitlinkit', 'fruit-link-it', 'fruit-linkit']])
            ->one();
        if($row)
        {
            $this->delete('{{%plugins}}', ['id' => $row['id']]);
        }

        // Get the field data from the project config
        $fields = (new \craft\db\Query())
            ->select(['uid', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['in', 'type', ['FruitLinkIt']])
            ->all();
        foreach ($fields as $field)
        {
            if (isset($field['type']) && $field['type'] === 'FruitLinkIt')
            {
                $type = LinkitField::class;
                $settings = $this->_migrateFieldSettings($field['settings'] ? Json::decode($field['settings']) : null);

                $field['type'] = $type;
                $field['settings'] = $settings;

                $this->update('{{%fields}}', [
                    'type' => LinkitField::class,
                    'settings' => Json::encode($settings)
                ], ['uid' => $field['uid']]);
            }
        }
    }

    private function _migrateFieldSettings($oldSettings)
    {
        if(!$oldSettings)
        {
            return null;
        }

        $linkitField = new LinkitField();

        $newSettings = $linkitField->getSettings();
        $newSettings['defaultText'] = $oldSettings['defaultText'] ?? '';
        $newSettings['allowTarget'] = $oldSettings['allowTarget'] ?? 0;
        $newSettings['allowCustomText'] = $oldSettings['allowCustomText'] ?? 0;

        if($oldSettings['types'] ?? false)
        {
            foreach ($oldSettings['types'] as $oldType)
            {
                switch ($oldType)
                {
                    case 'email':
                        $newSettings['types'][Email::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'custom':
                        $newSettings['types'][Url::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'tel':
                        $newSettings['types'][Phone::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'entry':
                        $newSettings['types'][Entry::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['entrySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['entrySelectionLabel'] ?? '',
                        ];
                        break;

                    case 'category':
                        $newSettings['types'][Category::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['categorySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['categorySelectionLabel'] ?? '',
                        ];
                        break;

                    case 'asset':
                        $newSettings['types'][Asset::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['assetSources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['assetSelectionLabel'] ?? '',
                        ];
                        break;

                    case 'product':
                        $newSettings['types'][Product::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['entrySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['entrySelectionLabel'] ?? '',
                        ];
                        break;
                }
            }
        }

        return $newSettings;
    }

    public function safeDown()
    {
        return true;
    }
}


