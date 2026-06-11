<?php
// 开发历程 Section
?>

<section class="px-4 py-16">
    <div class="max-w-4xl mx-auto">

        <!-- 品牌介绍区 -->
        <div class="scroll-reveal text-center mb-20">
            <div class="mb-6">
                <?php
                $logo = $settings['site_logo'] ?? '';
                if (!empty($logo)):
                ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="w-20 h-20 mx-auto rounded-full shadow-md object-cover">
                <?php else: ?>
                    <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-br from-[#DDB8B8] to-[#A8C5DA] flex items-center justify-center shadow-md">
                        <i class="ph ph-flower-tulip text-3xl text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h2 class="text-3xl md:text-4xl font-serif text-[#3E3640] mb-3">暮想 <span class="text-lg font-normal text-[#A8C5DA]">Museve</span></h2>
            <p class="text-[#DDB8B8] text-lg font-serif mb-8">在薄暮时分，温柔地想起。</p>

            <div class="max-w-2xl mx-auto bg-white/50 backdrop-blur-md rounded-2xl border border-white/50 shadow-md p-8 text-left">
                <div class="flex items-center gap-2 mb-4">
                    <i class="ph ph-heart text-[#DDB8B8] text-xl"></i>
                    <h3 class="text-xl font-serif text-[#3E3640]">关于暮想</h3>
                </div>
                <p class="text-[#5A5055] leading-relaxed font-serif">
                    暮想（Museve）是一个情感化的纪念网站，旨在用最温柔的方式，记录那些值得被珍藏的回忆。每一个像素都在轻声说：你的回忆，值得被温柔珍藏。
                </p>
                <p class="text-[#5A5055] leading-relaxed font-serif mt-4">
                    我们相信，时光如水，回忆如花。每一段故事、每一句悄悄话、每一篇文章，都是生命中不可复制的瞬间。暮想希望成为这些瞬间的温柔容器。
                </p>
            </div>
        </div>

        <!-- 开发时间轴 -->
        <div class="scroll-reveal mb-20">
            <h3 class="text-2xl font-serif text-[#3E3640] text-center mb-12">
                <i class="ph ph-clock-counter-clockwise text-[#A8C5DA]"></i> 开发历程
            </h3>

            <div class="relative">
                <!-- 中线 -->
                <div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#DDB8B8] via-[#A8C5DA] to-[#DDB8B8] opacity-40 transform md:-translate-x-1/2"></div>

                <?php
                // 从数据库加载里程碑
                $db = getDB();
                $milestones = $db->query("SELECT * FROM milestones ORDER BY sort_order ASC")->fetchAll();
                ?>

                <?php foreach ($milestones as $idx => $item): ?>
                    <?php $isLeft = ($idx % 2 === 0); ?>
                    <div class="scroll-reveal relative flex items-start mb-8 <?= $isLeft ? 'md:flex-row' : 'md:flex-row-reverse' ?>">
                        <!-- 节点 -->
                        <div class="absolute left-2 md:left-1/2 w-5 h-5 rounded-full bg-white border-3 border-[#A8C5DA] shadow transform translate-x-1/2 md:-translate-x-1/2 z-10 mt-1 flex items-center justify-center">
                            <div class="w-2 h-2 rounded-full bg-[#DDB8B8]"></div>
                        </div>
                        <!-- 内容卡片 -->
                        <div class="ml-10 md:ml-0 <?= $isLeft ? 'md:w-[calc(50%-2rem)] md:pr-8 md:text-right' : 'md:w-[calc(50%-2rem)] md:pl-8' ?>">
                            <div class="bg-white/60 backdrop-blur-md rounded-xl p-5 shadow-sm border border-white/50 hover:shadow-md transition-shadow group">
                                <div class="flex items-center gap-2 <?= $isLeft ? 'md:justify-end' : '' ?> mb-2">
                                    <i class="ph <?= htmlspecialchars($item['icon'] ?? 'ph-flower-tulip') ?> text-[#A8C5DA] text-lg"></i>
                                    <time class="text-sm text-[#A8C5DA] font-semibold"><?= htmlspecialchars($item['date']) ?></time>
                                </div>
                                <h4 class="text-lg font-serif text-[#3E3640] mb-2 group-hover:text-[#DDB8B8] transition-colors"><?= htmlspecialchars($item['title']) ?></h4>
                                <p class="text-sm text-[#8E827F] leading-relaxed"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 技术栈 -->
        <div class="scroll-reveal mb-20">
            <h3 class="text-2xl font-serif text-[#3E3640] text-center mb-8">
                <i class="ph ph-code text-[#A8C5DA]"></i> 技术栈
            </h3>

            <?php
            $techs = [
                ['PHP 8.0+', '后端语言', 'ph-file-code', '#DDB8B8'],
                ['MySQL 8.0+', '数据库', 'ph-database', '#A8C5DA'],
                ['Tailwind CSS', '前端样式', 'ph-paint-brush', '#DDB8B8'],
                ['Alpine.js', '前端交互', 'ph-lightning', '#A8C5DA'],
                ['TinyMCE', '文章编辑器', 'ph-text-aa', '#DDB8B8'],
                ['Phosphor Icons', '图标库', 'ph-asterisk', '#A8C5DA'],
                ['PDO + bcrypt', '安全与数据库', 'ph-shield-check', '#DDB8B8'],
                ['PHPMailer', '邮件服务', 'ph-envelope-simple', '#A8C5DA'],
            ];
            ?>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($techs as $tech): ?>
                    <div class="bg-white/60 backdrop-blur-md rounded-xl p-5 text-center border border-white/50 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all group">
                        <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center mb-3" style="background: <?= $tech[3] ?>20;">
                            <i class="ph <?= $tech[2] ?> text-xl" style="color: <?= $tech[3] ?>;"></i>
                        </div>
                        <p class="text-sm font-semibold text-[#3E3640]"><?= $tech[0] ?></p>
                        <p class="text-xs text-[#8E827F] mt-1"><?= $tech[1] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<style>
.scroll-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}
.scroll-reveal.revealed {
    opacity: 1;
    transform: translateY(0);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const reveals = document.querySelectorAll('.scroll-reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    reveals.forEach(el => observer.observe(el));
});
</script>
