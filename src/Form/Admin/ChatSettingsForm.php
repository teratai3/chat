<?php

namespace Drupal\chat\Form\Admin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\chat\Config\ChatConfig;

class ChatSettingsForm extends ConfigFormBase
{

    const CONFIG_NAME = 'chat.settings';

    /**
     * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder.
     */
    protected $routeBuilder;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'chat_settings_form';
    }

    /**
     * Class constructor.
     */
    public function __construct(RouteBuilder $route_builder)
    {
        $this->routeBuilder = $route_builder;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('router.builder')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            self::CONFIG_NAME,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config(self::CONFIG_NAME);

        $form['description'] = [
            '#type' => 'markup',
            '#markup' => '<p>このページでは、Chatモジュールの通信に必要な初期生成されるキーをリセット出来ます。</p>',
        ];

        return parent::buildForm($form, $form_state);
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $key = ChatConfig::generateKey(16);
        $iv = ChatConfig::generateKey(16);
        $jwt_secret_key = ChatConfig::generateKey(32);

        $this->config(self::CONFIG_NAME)
            ->set('openssl_key', $key)
            ->set('openssl_iv', $iv)
            ->set('jwt_secret_key', $jwt_secret_key)
            ->save();

        $this->routeBuilder->rebuild();
        parent::submitForm($form, $form_state);
    }
}
