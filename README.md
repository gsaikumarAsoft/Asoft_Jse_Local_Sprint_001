## JSE Broker Direct Market Access Tool [LARAVEL / VUE Application Deployment ]

This document is intended to act as a guideline for deployments of the LARAVEL / VUE Application Environment On (AWS)

- COPY JPEN :Lightsail Instance - SSH to prompt
- Run **cd ~/stack/nginx**
- Run **sudo rm -rf .git**
- Run **sudo git init**
- Run **sudo git clone https://github.com/Innovate-10x/JSE_DMA.git**
- Run **sudo mv html html-old** (Rename the current html folder to something else)
- Run **sudo mv broker-dma-tool.git/ html/** (Now that the new repository has been cloned lets rename the folder to htmls so nginx recognizes it)
- Run **cd html**
- Run **sudo composer install**
- Run **sudo chmod -R 777 storage**
- Run **sudo /opt/bitnami/ctlscript.sh restart**



## My Sql Database Configuration

- Check the parent folder **(htm/.env)**  for an **(.env)**, if it does not exist run the following command (sudo nano .env)
- Run **sudo vim .env** to open the file and change the **DBHOST** value to **localhost**
- Run **cd /home/bitnami** followed by **vim bitnami_credentials**
- Copy the default password {The default password is **{-----------}**  followed by **:q!**
- Run **cd /home/bitnami/stack/nginx/html**
- Run **sudo vim .env** to open the file and change the DBPASSWORD value to the default password received in step (4)

## Jason C Lawrence
## Innovate 10x
## February - March 2019# JSE_BROKER_DMA_tool
