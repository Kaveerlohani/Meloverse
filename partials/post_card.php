<?php
declare(strict_types=1);
/** @var array<string,mixed> $post */
/** @var ?array $currentUser */
$pid = (int) $post['id'];
$audioUrl = meloverse_public_storage_url((string) $post['audio_path']);
$avatar = !empty($post['avatar_path']) ? meloverse_public_storage_url((string) $post['avatar_path']) : '';
$liked = !empty($post['liked_by_me']);
$bm = !empty($post['bookmarked']);
$tags = meloverse_post_hashtags($pid);
?>
<article class="mv-card mv-hover-glow" data-post-id="<?= $pid ?>">
    <header class="mv-card__head">
        <a href="profile.php?u=<?= meloverse_h((string) $post['username']) ?>" class="mv-card__user">
            <span class="mv-avatar"><?php if ($avatar): ?><img src="<?= meloverse_h($avatar) ?>" alt=""><?php else: ?><span class="mv-avatar__ph"><?= meloverse_h(mb_strtoupper(mb_substr((string) $post['username'], 0, 1))) ?></span><?php endif; ?></span>
            <span>
                <span class="mv-card__name"><?= meloverse_h((string) ($post['display_name'] ?: $post['username'])) ?></span>
                <span class="mv-muted">@<?= meloverse_h((string) $post['username']) ?></span>
            </span>
        </a>
        <time class="mv-muted mv-card__time" datetime="<?= meloverse_h((string) $post['created_at']) ?>"><?= meloverse_h(date('M j, Y g:i A', strtotime((string) $post['created_at']))) ?></time>
    </header>
    <h2 class="mv-card__title"><a href="post.php?id=<?= $pid ?>"><?= meloverse_h((string) $post['title']) ?></a></h2>
    <?php if (!empty($post['description'])): ?>
        <p class="mv-card__desc"><?= nl2br(meloverse_h((string) $post['description'])) ?></p>
    <?php endif; ?>
    <?php if ($tags): ?>
        <div class="mv-tags"><?php foreach ($tags as $t): ?>
            <a href="tag.php?h=<?= meloverse_h($t) ?>" class="mv-tag">#<?= meloverse_h($t) ?></a>
        <?php endforeach; ?></div>
    <?php endif; ?>
    <div class="mv-player" data-mv-player>
        <audio preload="metadata" src="<?= meloverse_h($audioUrl) ?>"></audio>
        <div class="mv-player__row">
            <button type="button" class="mv-iconbtn mv-play-toggle" aria-label="Play">▶</button>
            <div class="mv-player__barwrap">
                <div class="mv-player__bar"><div class="mv-player__fill"></div></div>
            </div>
            <span class="mv-player__time"><span class="mv-cur">0:00</span> / <span class="mv-dur"><?php
                $ds = (int) ($post['duration_seconds'] ?? 0);
                if ($ds > 0) {
                    $m = intdiv($ds, 60);
                    $s = $ds % 60;
                    echo meloverse_h(sprintf('%d:%02d', $m, $s));
                } else {
                    echo '--:--';
                }
            ?></span></span>
        </div>
        <div class="mv-player__volrow">
            <label class="mv-muted">Vol</label>
            <input type="range" class="mv-vol" min="0" max="1" step="0.05" value="1" aria-label="Volume">
        </div>
    </div>
    <footer class="mv-card__actions">
        <?php if ($currentUser): ?>
            <button type="button" class="mv-action mv-like<?= $liked ? ' is-on' : '' ?>" data-like data-post="<?= $pid ?>"><?= $liked ? '♥' : '♡' ?> <span class="mv-like-count"><?= (int) ($post['likes_count'] ?? 0) ?></span></button>
            <a class="mv-action" href="post.php?id=<?= $pid ?>#comments">💬 <?= (int) ($post['comments_count'] ?? 0) ?></a>
            <button type="button" class="mv-action mv-bookmark<?= $bm ? ' is-on' : '' ?>" data-bookmark data-post="<?= $pid ?>"><?= $bm ? '★' : '☆' ?> Save</button>
            <button type="button" class="mv-action" data-share data-url="<?= meloverse_h(meloverse_base_url() . '/post.php?id=' . $pid) ?>">Share</button>
        <?php else: ?>
            <span class="mv-muted mv-action">♡ <?= (int) ($post['likes_count'] ?? 0) ?></span>
            <a class="mv-action" href="post.php?id=<?= $pid ?>#comments">💬 <?= (int) ($post['comments_count'] ?? 0) ?></a>
            <button type="button" class="mv-action" data-share data-url="<?= meloverse_h(meloverse_base_url() . '/post.php?id=' . $pid) ?>">Share</button>
        <?php endif; ?>
        <span class="mv-muted mv-action">▶ <?= (int) ($post['plays_count'] ?? 0) ?> plays</span>
    </footer>
</article>
