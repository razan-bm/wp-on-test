<?php
/**
 * Button from the reusable "button" group (see Reusable Components)
 *
 *   get_template_part('components/global/button/button', null, ['button' => $button, 'class' => 'w-full']);
 *   echo get_button($button);
 */
$button = $args['button'] ?? [];
$class = $args['class'] ?? '';

if (empty($button['button_text'])) {
  return;
}

$type = $button['button_type'] ?? 'internal';
$href = match ($type) {
  'external' => $button['url'] ?? '',
  'tel' => ($number = preg_replace('/[^0-9+]/', '', $button['link_number'] ?? '')) ? "tel:$number" : '',
  'mail' => ($email = sanitize_email($button['link_email'] ?? '')) ? "mailto:$email" : '',
  default => $button['page_link'] ?? '',
};
if (!empty($button['fragment_identifier']) && in_array($type, ['internal', 'external'], true)) {
  $href .= '#' . ltrim($button['fragment_identifier'], '#');
}
// esc_url also drops unsafe links like javascript:
$href = esc_url($href);
if (!$href) {
  return;
}

$style = $button['button_display_type'] ?? 'primary';
$style_class = ['primary' => 'btn-primary', 'secondary' => 'btn-secondary', 'link' => 'btn-link'][$style] ?? 'btn-primary';
$is_external = $type === 'external';

$icon = '';
if (!empty($button['add_icon']) && !empty($button['icon'])) {
  $icon = wp_get_attachment_image($button['icon'], 'thumbnail', false, ['class' => 'size-5 shrink-0 object-contain', 'alt' => '', 'aria-hidden' => 'true']);
} elseif ($style === 'link') {
  $icon = '<span class="size-5 shrink-0" aria-hidden="true">' . get_icon('arrow-narrow-right') . '</span>';
}
$icon_right = $style === 'link' && empty($button['add_icon']) || ($button['icon_position'] ?? 'left') === 'right';
?>
<a href="<?= $href ?>" class="<?= esc_attr(trim("$style_class $class")) ?>"<?= $is_external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
  <?php if ($icon && !$icon_right) echo $icon; ?>
  <span><?= esc_html($button['button_text']) ?></span>
  <?php if ($is_external) : ?><span class="sr-only">(opens in a new tab)</span><?php endif; ?>
  <?php if ($icon && $icon_right) echo $icon; ?>
</a>
