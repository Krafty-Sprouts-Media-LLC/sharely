<?php

declare(strict_types=1);

namespace KraftySprouts\Sharely\Sharing;

/**
 * One published item as Sharely shares it: its address and the words and
 * image a network is given, with the item's own overrides already chosen.
 *
 * @since 1.0.0
 */
final readonly class SharedItem
{
    /**
     * @param  string  $id  the item's ID
     * @param  string  $url  its live address, absolute
     * @param  string  $title  the share title: the social title, else the title
     * @param  string  $description  the share description, possibly empty
     * @param  string|null  $image  the share image's absolute address, if known
     * @param  string|null  $pinImage  the image chosen for Pinterest, if any
     * @param  string|null  $pinDescription  the description chosen for Pinterest, if any
     * @param  string|null  $shareText  the words chosen for networks that post text, if any
     * @param  string|null  $shortUrl  the short address made for the item, if any
     *
     * @since 1.0.0
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $title,
        public string $description = '',
        public ?string $image = null,
        public ?string $pinImage = null,
        public ?string $pinDescription = null,
        public ?string $shareText = null,
        public ?string $shortUrl = null,
    ) {}

    /**
     * The words networks that post text are given: the item's own share
     * text, else its share title.
     *
     * @since 1.0.0
     */
    public function words(): string
    {
        $text = trim((string) $this->shareText);

        return $text !== '' ? $text : $this->title;
    }
}
