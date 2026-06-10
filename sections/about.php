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
                $timeline = [
                    ['2025-12', 'V1.0 项目诞生', '暮想项目正式立项，确定品牌方向与设计理念，梦想从这里起航。', 'ph-rocket-launch'],
                    ['2026-01', 'V2.0 回忆时光轴', '完成品牌色彩体系、回忆时光轴核心功能开发，记录时间的温柔。', 'ph-clock-countdown'],
                    ['2026-02', 'V3.0 文章系统', '文章编辑与发布系统上线，支持图文混排，让文字自由表达。', 'ph-article'],
                    ['2026-03', 'V4.0 后台管理', '后台管理系统搭建完成，涵盖内容管理、用户管理等核心功能。', 'ph-gear-six'],
                    ['2026-04', 'V4.1 功能完善', '悄悄话、图片上传、评论系统等功能迭代，体验不断打磨。', 'ph-puzzle-piece'],
                    ['2026-05', 'V5.0 情感化设计', '引入情感化设计理念，滚动动画、品牌沉浸感全面升级。', 'ph-palette'],
                    ['2026-06', 'V5.2 标准化重构', '全面标准化重构，代码规范、性能优化、组件化开发。', 'ph-seal-check'],
                ];
                ?>

                <?php foreach ($timeline as $idx => $item): ?>
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
                                    <i class="ph <?= $item[3] ?> text-[#A8C5DA] text-lg"></i>
                                    <time class="text-sm text-[#A8C5DA] font-semibold"><?= $item[0] ?></time>
                                </div>
                                <h4 class="text-lg font-serif text-[#3E3640] mb-2 group-hover:text-[#DDB8B8] transition-colors"><?= $item[1] ?></h4>
                                <p class="text-sm text-[#8E827F] leading-relaxed"><?= $item[2] ?></p>
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

        <!-- 联系方式 & 友情链接 -->
        <div class="scroll-reveal grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- 联系方式 -->
            <div class="bg-white/60 backdrop-blur-md rounded-2xl border border-white/50 shadow-md p-6">
                <h3 class="text-lg font-serif text-[#3E3640] mb-4">
                    <i class="ph ph-paper-plane-tilt text-[#A8C5DA]"></i> 联系我们
                </h3>
                <div class="space-y-3">
                    <a href="mailto:admin@museve.com" class="flex items-center gap-3 text-sm text-[#5A5055] hover:text-[#DDB8B8] transition-colors group">
                        <i class="ph ph-envelope text-[#A8C5DA] group-hover:text-[#DDB8B8] transition-colors"></i>
                        admin@museve.com
                    </a>
                    <a href="https://github.com" target="_blank" class="flex items-center gap-3 text-sm text-[#5A5055] hover:text-[#DDB8B8] transition-colors group">
                        <i class="ph ph-github-logo text-[#A8C5DA] group-hover:text-[#DDB8B8] transition-colors"></i>
                        GitHub
                    </a>
                    <div class="flex items-center gap-3 text-sm text-[#5A5055]">
                        <i class="ph ph-clock text-[#A8C5DA]"></i>
                        <span>通常在 24 小时内回复</span>
                    </div>
                </div>
            </div>

            <!-- 友情链接 -->
            <div class="bg-white/60 backdrop-blur-md rounded-2xl border border-white/50 shadow-md p-6">
                <h3 class="text-lg font-serif text-[#3E3640] mb-4">
                    <i class="ph ph-link text-[#A8C5DA]"></i> 友情链接
                </h3>
                <div class="space-y-3">
                    <a href="#" class="flex items-center gap-3 text-sm text-[#5A5055] hover:text-[#DDB8B8] transition-colors group">
                        <i class="ph ph-arrow-square-out text-[#A8C5DA] group-hover:text-[#DDB8B8] transition-colors"></i>
                        欢迎交换友情链接
                    </a>
                    <p class="text-xs text-[#8E827F] leading-relaxed pl-7">
                        如果你也喜欢记录与回忆，欢迎通过邮件联系我们交换链接。
                    </p>
                </div>
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
