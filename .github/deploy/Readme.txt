
#======================================
# NEW/REPLACE DEPLOYMENT
#======================================
cd ~/stack/nginx/html
sudo php artisan optimize:clear
sudo php artisan route:clear
sudo php artisan config:clear 
sudo php artisan cache:clear
sudo php artisan view:clear
sudo php artisan key:generate
sudo php artisan migrate:fresh --seed
sudo php artisan config:cache
sudo /opt/bitnami/ctlscript.sh restart

#======================================
# LOAD UPDATED .ENV 
#======================================
sudo php artisan route:clear
sudo php artisan cache:clear
sudo php artisan view:clear
sudo php artisan config:cache
sudo /opt/bitnami/ctlscript.sh restart
sudo php artisan cache:clear
#=======================================



