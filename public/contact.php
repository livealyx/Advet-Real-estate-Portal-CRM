<?php
// FILE: public/contact.php
session_start();
require_once '../config/db.php';
$solidNav  = true;
$pageTitle = 'Contact Us';
$pageDesc  = 'Get in touch with the Advet Buildwell studio. We\'d love to hear about your next chapter.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">

            <!-- Page Header -->
            <div class="mb-24 reveal">
                <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Get in Touch</p>
                <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight max-w-3xl">
                    Let's begin a <span class="italic text-muted">conversation.</span>
                </h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-start">

                <!-- Contact Form -->
                <div class="reveal reveal-delay-1">
                    <form method="POST" action="<?= BASE ?>actions/submit-inquiry.php" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Full Name *</label>
                                <input id="contact_name" type="text" name="name" required placeholder="Your name"
                                       class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Email *</label>
                                <input id="contact_email" type="email" name="email" required placeholder="email@example.com"
                                       class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Phone</label>
                            <input id="contact_phone" type="tel" name="phone" placeholder="+1 (555) 000-0000"
                                   class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Message *</label>
                            <textarea id="contact_message" name="message" required rows="7" placeholder="Tell us about your vision, your timeline, and what you're looking for…"
                                      class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all resize-none"></textarea>
                        </div>
                        <button type="submit" id="contact_submit"
                                class="w-full py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest hover:bg-neutral-800 transition-all shadow-lg transform hover:-translate-y-0.5">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Studio Info -->
                <div class="reveal reveal-delay-2">
                    <div class="space-y-16">
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-6">Studio Location</p>
                            <div class="flex gap-4 group">
                                <div class="p-2.5 bg-accent/10 rounded-xl text-accent group-hover:bg-accent group-hover:text-white transition-all duration-300 h-fit mt-1">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 18C19.2447 18.4244 20 18.9819 20 19.5925C20 20.9221 16.4183 22 12 22C7.58172 22 4 20.9221 4 19.5925C4 18.9819 4.75527 18.4244 6 18" stroke-linecap="round"/><path d="M15 9.5C15 11.1569 13.6569 12.5 12 12.5C10.3431 12.5 9 11.1569 9 9.5C9 7.84315 10.3431 6.5 12 6.5C13.6569 6.5 15 7.84315 15 9.5Z"/><path d="M12 2C16.0588 2 19.5 5.42803 19.5 9.5869C19.5 13.812 16.0028 16.777 12.7725 18.7932C12.5371 18.9287 12.2709 19 12 19C11.7291 19 11.4629 18.9287 11.2275 18.7932C8.00325 16.7573 4.5 13.8266 4.5 9.5869C4.5 5.42803 7.9412 2 12 2Z"/></svg>
                                </div>
                                <p class="text-2xl font-serif font-light leading-relaxed">
                                    <?= nl2br(e($siteSettings['studio_address'] ?? "1042 Minimalist Way\nLos Angeles, CA 90026")) ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-6">Contact</p>
                            <div class="space-y-6">
                                <!-- Email -->
                                <div class="flex items-center gap-4 group">
                                    <div class="p-2.5 bg-accent/10 rounded-xl text-accent group-hover:bg-accent group-hover:text-white transition-all duration-300">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M2 6L8.91302 9.91697C11.4616 11.361 12.5384 11.361 15.087 9.91697L22 6"/><path d="M2.01577 13.4756C2.08114 16.5412 2.11383 18.0739 3.24496 19.2094C4.37608 20.3448 5.95033 20.3843 9.09883 20.4634C11.0393 20.5122 12.9607 20.5122 14.9012 20.4634C18.0497 20.3843 19.6239 20.3448 20.7551 19.2094C21.8862 18.0739 21.9189 16.5412 21.9842 13.4756C22.0053 12.4899 22.0053 11.5101 21.9842 10.5244C21.9189 7.45886 21.8862 5.92609 20.7551 4.79066C19.6239 3.65523 18.0497 3.61568 14.9012 3.53657C12.9607 3.48781 11.0393 3.48781 9.09882 3.53656C5.95033 3.61566 4.37608 3.65521 3.24495 4.79065C2.11382 5.92608 2.08114 7.45885 2.01576 10.5244C1.99474 11.5101 1.99475 12.4899 2.01577 13.4756Z"/></svg>
                                    </div>
                                    <p class="text-base font-light">
                                        <?php $email = $siteSettings['contact_email'] ?? 'studio@advetbuildwell.com'; ?>
                                        <a href="mailto:<?= e($email) ?>" class="text-foreground hover:text-accent transition-colors border-b border-transparent hover:border-accent/30 lowercase">
                                            <?= e($email) ?>
                                        </a>
                                    </p>
                                </div>

                                <!-- Mobile -->
                                <div class="flex items-center gap-4 group">
                                    <div class="p-2.5 bg-accent/10 rounded-xl text-accent group-hover:bg-accent group-hover:text-white transition-all duration-300">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.91186 10.5413L7.55229 7.90088C8.09091 7.36227 8.27728 6.56642 8.05944 5.83652C7.8891 5.26577 7.69718 4.57964 7.56961 3.99292C7.45162 3.45027 6.97545 3 6.42012 3H4.91186C3.8012 3 2.88911 3.90384 3.01094 5.0078C3.93709 13.3996 10.6004 20.0629 18.9922 20.9891C20.0962 21.1109 21 20.1988 21 19.0881V17.5799C21 17.0246 20.5479 16.569 20.0015 16.4696C19.3988 16.36 18.7611 16.1804 18.2276 16.0103C17.4611 15.7659 16.6091 15.9377 16.0403 16.5065L13.4587 19.0881"/></svg>
                                    </div>
                                    <p class="text-base font-light tracking-wide text-foreground/80"><?= e($siteSettings['studio_phone'] ?? '+1 (424) 000-0000') ?></p>
                                </div>

                                <!-- Landline -->
                                <?php if (!empty($siteSettings['studio_landline'])): ?>
                                <div class="flex items-center gap-4 group">
                                    <div class="p-2.5 bg-accent/10 rounded-xl text-accent group-hover:bg-accent group-hover:text-white transition-all duration-300">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.74038 14.3685L6.69351 12.9816C7.24445 12.5904 7.80305 12.3282 8.44034 12.1585C9.17201 11.9636 9.5 11.5644 9.5 10.711C9.5 8.54628 14.5 8.31594 14.5 10.711C14.5 11.5644 14.828 11.9636 15.5597 12.1585C16.202 12.3295 16.7599 12.5934 17.3065 12.9816L19.2596 14.3685C20.1434 14.9961 20.5547 15.2995 20.7842 15.7819C21 16.2358 21 16.768 21 17.8324C21 19.7461 21 20.703 20.4642 21.3164C19.8152 22.0593 18.128 21.9955 17.0917 21.9955H6.90833C5.87197 21.9955 4.21909 22.0986 3.5358 21.3164C3 20.703 3 19.7461 3 17.8324C3 16.768 3 16.2358 3.21584 15.7819C3.44526 15.2995 3.85662 14.9961 4.74038 14.3685Z"/><path d="M14 17C14 18.1046 13.1046 19 12 19C10.8954 19 10 18.1046 10 17C10 15.8954 10.8954 15 12 15C13.1046 15 14 15.8954 14 17Z"/><path d="M6.96014 3.69772C5.6417 4.07415 4.69384 4.54112 3.82645 5.10455C2.45318 5.9966 1.86443 7.60404 2.02607 9.15513C2.09439 9.81068 2.62064 10.1241 3.23089 9.95455C3.69451 9.82571 4.15888 9.7003 4.61961 9.56364C5.96706 9.16397 6.28399 8.67812 6.47124 7.29885L6.96014 3.69772ZM6.96014 3.69772C10.2186 2.76743 13.7814 2.76743 17.0399 3.69772M17.0399 3.69772C18.3583 4.07415 19.3062 4.54112 20.1735 5.10455C21.5468 5.9966 22.1356 7.60404 21.9739 9.15513C21.9056 9.81068 21.3794 10.1241 20.7691 9.95455C20.3055 9.82571 19.8411 9.7003 19.3804 9.56364C18.0329 9.16397 17.716 8.67812 17.5288 7.29885L17.0399 3.69772Z"/></svg>
                                    </div>
                                    <p class="text-base font-light tracking-wide text-foreground/80"><?= e($siteSettings['studio_landline']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-6">Studio Hours</p>
                            <div class="space-y-2 text-sm text-muted font-light">
                                <div class="flex justify-between border-b border-sand/30 pb-2">
                                    <span>Monday – Friday</span><span><?= e($siteSettings['hours_mon_fri'] ?? '9:00 AM – 6:00 PM') ?></span>
                                </div>
                                <div class="flex justify-between border-b border-sand/30 pb-2">
                                    <span>Saturday</span><span><?= e($siteSettings['hours_sat'] ?? '10:00 AM – 4:00 PM') ?></span>
                                </div>
                                <div class="flex justify-between pb-2">
                                    <span>Sunday</span><span class="italic"><?= e($siteSettings['hours_sun'] ?? 'By Appointment') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-surface/50 rounded-[2rem] p-8 border border-sand/30">
                            <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-4">Response Time</p>
                            <p class="text-sm font-light text-muted leading-relaxed">
                                We respond to all inquiries within 24 hours during studio hours. For urgent matters, please call us directly.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
