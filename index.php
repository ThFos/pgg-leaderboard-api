// Fallback head URL
        $headUrl = "https://mc-heads.net/avatar/" . urlencode($username) . "/50";

        if (!empty($skinId)) {
            // 1. Μετάφραση του minesk.in link στο πραγματικό texture URL μέσω API
            if (strpos($skinId, 'minesk.in/') !== false) {
                $mineskinId = basename(parse_url($skinId, PHP_URL_PATH));
                $apiContext = stream_context_create([
                    'http' => ['timeout' => 3, 'user_agent' => 'PGG-Legacy-Leaderboard']
                ]);
                // Κλήση στο Mineskin API
                $apiJson = @file_get_contents("https://api.mineskin.org/get/uuid/" . $mineskinId, false, $apiContext);
                
                if ($apiJson !== false) {
                    $data = json_decode($apiJson, true);
                    if (!empty($data['data']['texture']['url'])) {
                        // Αντικαθιστούμε το HTML URL με το καθαρό .png από το textures.minecraft.net
                        $skinId = $data['data']['texture']['url']; 
                    }
                }
            }

            // 2. Ελέγχουμε αν είναι URL (Imgur, Mineskin -πλέον μεταφρασμένο-, κλπ.)
            if ($skinType === 'URL' || filter_var($skinId, FILTER_VALIDATE_URL) || strpos($skinId, 'http') === 0) {
                if ($hasGd) {
                    $context = stream_context_create([
                        'http' => ['timeout' => 4, 'user_agent' => 'Mozilla/5.0']
                    ]);
                    $skinContent = @file_get_contents($skinId, false, $context);
                    
                    if ($skinContent !== false) {
                        $skinImg = @imagecreatefromstring($skinContent);
                        if ($skinImg !== false) {
                            $headImg = imagecreatetruecolor(50, 50);
                            imagesavealpha($headImg, true);
                            $transparent = imagecolorallocatealpha($headImg, 0, 0, 0, 127);
                            imagefill($headImg, 0, 0, $transparent);
                            
                            // Κοπή προσώπου και καπέλου
                            imagecopyresampled($headImg, $skinImg, 0, 0, 8, 8, 50, 50, 8, 8);
                            imagecopyresampled($headImg, $skinImg, 0, 0, 40, 8, 50, 50, 8, 8);
                            
                            ob_start();
                            imagepng($headImg);
                            $headUrl = 'data:image/png;base64,' . base64_encode(ob_get_clean());
                            
                            imagedestroy($skinImg);
                            imagedestroy($headImg);
                        }
                    }
                }
            } else {
                // Αν είναι απλό όνομα skin
                $headUrl = "https://mc-heads.net/avatar/" . urlencode($skinId) . "/50";
            }
        }
