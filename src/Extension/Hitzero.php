<?php
/**
 * HitZero — secure article hit counter resets in Joomla 5/6 administration.
 * Based on HitZero 1.3.1 by UWiX. GPL-2.0-or-later.
 */
namespace HitzeroRoot\Plugin\System\Hitzero\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Hitzero extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute' => 'handleReset',
            'onBeforeRender' => 'addInterface',
        ];
    }

    /** A dedicated POST endpoint, handled before com_content dispatch. */
    public function handleReset(Event $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $app = $this->getApplication();
        $input = $app->getInput();
        if ($input->getCmd('option') !== 'com_content'
            || $input->getCmd('hitzero_action') !== 'reset') {
            return;
        }

        $this->loadLanguage();
        $editId = max(0, $input->post->getInt('return_id', 0));
        $returnView = $input->post->getCmd('return_view', 'articles');
        // Only redirect to known admin com_content views; never accept arbitrary URLs.
        $listView = $returnView === 'featured' ? 'featured' : 'articles';
        $redirect = ($returnView === 'article' && $editId > 0)
            ? 'index.php?option=com_content&task=article.edit&id=' . $editId
            : 'index.php?option=com_content&view=' . $listView;

        // All invalid requests are rejected, including GET requests.
        if (strtoupper($input->getMethod()) !== 'POST' || !Session::checkToken('post')) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_HITZERO_ERROR_TOKEN'), 'error');
            $this->finish($redirect);
        }

        $rawIds = $input->post->get('hitzero_ids', [], 'array');
        if (!is_array($rawIds) || count($rawIds) === 0 || count($rawIds) > 500) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_HITZERO_ERROR_SELECTION'), 'warning');
            $this->finish($redirect);
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            // Accept only canonical positive integer IDs, not castable strings.
            if ((!is_string($rawId) && !is_int($rawId)) || !preg_match('/^[1-9][0-9]*$/D', (string) $rawId)) {
                $app->enqueueMessage(Text::_('PLG_SYSTEM_HITZERO_ERROR_SELECTION'), 'warning');
                $this->finish($redirect);
            }
            $ids[] = (int) $rawId;
        }
        $ids = array_values(array_unique($ids));
        $user = $app->getIdentity();
        if ((int) $user->id === 0) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_HITZERO_ERROR_PERMISSION'), 'error');
            $this->finish($redirect);
        }

        $db = $this->getDatabase();
        $changed = 0;
        $alreadyZero = 0;
        $notAllowed = 0;
        $missing = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $query = $db->getQuery(true)
                    ->select([$db->quoteName('id'), $db->quoteName('created_by'), $db->quoteName('hits')])
                    ->from($db->quoteName('#__content'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);
                $article = $db->setQuery($query)->loadObject();
                if (!$article) {
                    $missing++;
                    continue;
                }

                $asset = 'com_content.article.' . $id;
                $mayEdit = $user->authorise('core.edit', $asset)
                    || ((int) $article->created_by === (int) $user->id
                        && $user->authorise('core.edit.own', $asset));
                if (!$mayEdit) {
                    $notAllowed++;
                    continue;
                }
                if ((int) $article->hits === 0) {
                    $alreadyZero++;
                    continue;
                }

                // The WHERE predicate avoids an unnecessary write when hits changed to zero.
                $update = $db->getQuery(true)
                    ->update($db->quoteName('#__content'))
                    ->set($db->quoteName('hits') . ' = 0')
                    ->where($db->quoteName('id') . ' = ' . (int) $id)
                    ->where($db->quoteName('hits') . ' <> 0');
                $db->setQuery($update)->execute();
                $changed++;
            } catch (\Throwable $exception) {
                $errors++;
            }
        }

        if ($changed > 0) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_HITZERO_SUCCESS', $changed), 'message');
        }
        if ($alreadyZero > 0) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_HITZERO_ALREADY_ZERO', $alreadyZero), 'notice');
        }
        if ($notAllowed > 0) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_HITZERO_DENIED', $notAllowed), 'warning');
        }
        if ($missing > 0 || $errors > 0) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_HITZERO_FAILED', $missing + $errors), 'error');
        }

        $this->finish($redirect);
    }

    public function addInterface(Event $event): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('administrator')) {
            return;
        }
        $input = $app->getInput();
        if ($input->getCmd('option') !== 'com_content') {
            return;
        }
        $view = $input->getCmd('view', 'articles');
        $task = $input->getCmd('task', '');
        $editing = ($view === 'article' && $input->getCmd('layout') === 'edit')
            || $task === 'article.edit';
        if (!$editing && !in_array($view, ['articles', 'featured'], true)) {
            return;
        }
        $document = $app->getDocument();
        if (!$document instanceof HtmlDocument || (int) $app->getIdentity()->id === 0) {
            return;
        }
        $this->loadLanguage();
        $document->addScriptOptions('plg_system_hitzero', [
            'token' => Session::getFormToken(),
            'editing' => $editing,
            'listView' => $view === 'featured' ? 'featured' : 'articles',
            'editId' => $editing ? $input->getInt('id', 0) : 0,
            'labels' => [
                'single' => Text::_('PLG_SYSTEM_HITZERO_SINGLE'),
                'multiple' => Text::_('PLG_SYSTEM_HITZERO_MULTIPLE'),
                'confirmSingle' => Text::_('PLG_SYSTEM_HITZERO_CONFIRM_SINGLE'),
                'confirmMultiple' => Text::_('PLG_SYSTEM_HITZERO_CONFIRM_MULTIPLE'),
                'noneSelected' => Text::_('PLG_SYSTEM_HITZERO_ERROR_SELECTION'),
                'modalTitle' => Text::_('PLG_SYSTEM_HITZERO_MODAL_TITLE'),
                'cancel' => Text::_('PLG_SYSTEM_HITZERO_CANCEL'),
                'confirmButton' => Text::_('PLG_SYSTEM_HITZERO_CONFIRM_BUTTON'),
                'irreversible' => Text::_('PLG_SYSTEM_HITZERO_IRREVERSIBLE'),
            ],
        ]);
        $document->getWebAssetManager()->useScript('bootstrap.modal');
        $document->getWebAssetManager()->registerAndUseScript(
            'plg_system_hitzero.admin',
            'media/plg_system_hitzero/js/hitzero.js',
            [],
            ['defer' => true]
        );
    }

    private function finish(string $url): void
    {
        $this->getApplication()->redirect($url);
        $this->getApplication()->close();
    }
}
