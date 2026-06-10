<?php
// 开发历程 Section
?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <h2 class="text-3xl font-serif text-[#3E3640] text-center mb-6">开发历程</h2>
    <p class="text-center text-[#8E827F] mb-16">在薄暮时分，温柔地想起。</p>

    <!-- 品牌介绍 -->
    <div class="bg-white/50 backdrop-blur-md rounded-2xl border border-white/50 shadow-md p-8 mb-12">
        <h3 class="text-xl font-serif text-[#3E3640] mb-4">关于暮想</h3>
        <p class="text-[#5A5055] leading-relaxed font-serif">
            暮想（Museve）是一个情感化的纪念网站，旨在用最温柔的方式，记录那些值得被珍藏的回忆。每一个像素都在轻声说：你的回忆，值得被温柔珍藏。
        </p>
        <p class="text-[#5A5055] leading-relaxed font-serif mt-4">
            我们相信，时光如水，回忆如花。每一段故事、每一句悄悄话、每一篇文章，都是生命中不可复制的瞬间。暮想希望成为这些瞬间的温柔容器。
        </p>
    </div>

    <!-- 开发时间线 -->
    <div class="relative">
        <div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-0.5 bg-[#DDB8B8]/30 transform md:-translate-x-1/2"></div>

        <?php
        $timeline = [
            ['2026-01', '项目启动', '暮想项目正式立项，确定品牌方向与设计理念。'],
            ['2026-02', '设计定稿', '完成品牌色彩体系、页面布局与交互设计。'],
            ['2026-03', '后端开发', '数据库设计、API 接口开发、后台管理系统搭建。'],
            ['2026-04', '前端开发', '首页、回忆时光轴、悄悄话、文章等页面开发。'],
            ['2026-05', '功能完善', '用户系统、文章编辑器、图片上传等功能迭代。'],
            ['2026-06', '正式上线', '暮想 V5.2 标准化重构版发布上线。'],
        ];
        ?>

        <?php foreach ($timeline as $idx => $item): ?>
            <?php $isLeft = ($idx % 2 === 0); ?>
            <div class="relative flex items-start mb-10 <?= $isLeft ? 'md:flex-row' : 'md:flex-row-reverse' ?>">
                <!-- 节点 -->
                <div class="absolute left-2 md:left-1/2 w-4 h-4 rounded-full bg-[#A8C5DA] border-4 border-white shadow transform translate-x-1/2 md:-translate-x-1/2 z-10 mt-1"></div>
                <!-- 内容 -->
                <div class="ml-10 md:ml-0 <?= $isLeft ? 'md:w-[calc(50%-2rem)] md:pr-8 md:text-right' : 'md:w-[calc(50%-2rem)] md:pl-8' ?>">
                    <div class="bg-white/60 backdrop-blur-md rounded-xl p-5 shadow-sm border border-white/50">
                        <time class="text-sm text-[#A8C5DA] font-semibold"><?= $item[0] ?></time>
                        <h4 class="text-lg font-serif text-[#3E3640] mt-1 mb-2"><?= $item[1] ?></h4>
                        <p class="text-sm text-[#8E827F] leading-relaxed"><?= $item[2] ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 技术栈 -->
    <div class="bg-white/50 backdrop-blur-md rounded-2xl border border-white/50 shadow-md p-8 mt-12">
        <h3 class="text-xl font-serif text-[#3E3640] mb-4">技术栈</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <?php
            $techs = [
                ['PHP 8.0+', '后端语言'],
                ['MySQL 8.0+', '数据库'],
                ['Tailwind CSS', '前端样式'],
                ['Alpine.js', '前端交互'],
                ['TinyMCE', '文章编辑器'],
                ['bcrypt / PDO', '安全与数据库'],
            ];
            ?>
            <?php foreach ($techs as $tech): ?>
                <div class="bg-[#F9F7F4] rounded-lg p-3 text-center">
                    <p class="text-sm font-semibold text-[#3E3640]"><?= $tech[0] ?></p>
                    <p class="text-xs text-[#8E827F] mt-0.5"><?= $tech[1] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
