<?php

declare(strict_types=1);

namespace KraftySprouts\Sharely\Networks;

/**
 * One network or utility Sharely can draw a button for: its name, its
 * brand colour, how its button works, and the roles it can take.
 *
 * @since 1.0.0
 */
final readonly class Network
{
    /** A plain link to the network's own share address. @since 1.0.0 */
    public const string LINK = 'link';

    /** A link to a page of Sharely's, such as the Mastodon server picker. @since 1.0.0 */
    public const string PAGE = 'page';

    /** A button that needs a script, hidden until the script shows it. @since 1.0.0 */
    public const string SCRIPT = 'script';

    /**
     * @param  string  $id  the network's id, lower-case
     * @param  string  $name  its name as people know it; brand names are not translated
     * @param  string  $color  its brand colour, #rrggbb
     * @param  string  $kind  LINK, PAGE or SCRIPT
     * @param  bool  $share  whether it can share an item
     * @param  bool  $follow  whether the site can have a profile there for follow buttons
     * @param  bool  $highlight  whether it can share a highlighted passage
     * @param  bool  $sameTab  whether its link opens in the same tab (mail and messages)
     *
     * @since 1.0.0
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $color,
        public string $kind = self::LINK,
        public bool $share = true,
        public bool $follow = true,
        public bool $highlight = false,
        public bool $sameTab = false,
    ) {}

    /**
     * The words a share button is named by, for people using a screen reader.
     *
     * @since 1.0.0
     */
    public function shareLabel(): string
    {
        return match ($this->id) {
            'email' => __('Share by email'),
            'sms' => __('Share by text message'),
            'print' => __('Print this page'),
            'copy' => __('Copy the link to this page'),
            'sheet' => __('Share with an app on this device'),
            default => __('Share on :network', ['network' => $this->name]),
        };
    }

    /**
     * The words a follow button is named by.
     *
     * @since 1.0.0
     */
    public function followLabel(): string
    {
        return match ($this->id) {
            'email' => __('Write to us by email'),
            'rss' => __('Follow with a feed reader'),
            default => __('Follow us on :network', ['network' => $this->name]),
        };
    }

    /**
     * The word shown beside the icon when the buttons show labels.
     *
     * @since 1.0.0
     */
    public function shortLabel(): string
    {
        return match ($this->id) {
            'email' => __('Email'),
            'sms' => __('Text'),
            'print' => __('Print'),
            'copy' => __('Copy link'),
            'sheet' => __('Share'),
            'rss' => __('Feed'),
            default => $this->name,
        };
    }
}
