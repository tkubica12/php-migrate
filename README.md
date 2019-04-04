# PHP migrate demo
Example to package PHP app as container with ACR, deploy MySQL as a service a run container in multiple environments:
* local
* Azure Container Instances
* Azure App Services (WebApp)
* Azure Kubernetes Service

# Create Azure environment
az group create -n php -l westeurope
az mysql server create -l westeurope -g php -n mujmysql -u tomas -p Azure12345678 --sku-name B_Gen5_1
az mysql server firewall-rule create -g php -s mujmysql -n all --start-ip-address 0.0.0.0 --end-ip-address 255.255.255.255
az mysql db create -g php -s mujmysql -n todo
az acr create -n mujphpregistr -g php --sku Basic --admin-enabled true

export regUsername=$(az acr credential show -n mujphpregistr --query username -o tsv)
export regPassword=$(az acr credential show -n mujphpregistr --query passwords[0].value -o tsv)

# Build container
az acr build -t php/todo -r mujphpregistr .

# Create tables
mysql -h mujmysql.mysql.database.azure.com -u tomas@mujmysql -p < db.sql

# Run locally
docker.exe login -u $regUsername -p $regPassword mujphpregistr.azurecr.io
docker.exe run -d \
    -p 50000:80 \
    --name php \
    -e MYSQL_HOST="mujmysql.mysql.database.azure.com" \
    -e MYSQL_USERNAME="tomas@mujmysql" \
    -e MYSQL_PASSWORD="Azure12345678" \
    -e MYSQL_DB="todo" \
    mujphpregistr.azurecr.io/php/todo:latest

# Deploy ACI
az container create -g php -l northeurope \
    --ip-address Public \
    --name php-todo \
    --image mujphpregistr.azurecr.io/php/todo:latest \
    --cpu 1 \
    --memory 1.5 \
    --registry-login-server "mujphpregistr.azurecr.io" \
    -e MYSQL_HOST="mujmysql.mysql.database.azure.com" MYSQL_USERNAME="tomas@mujmysql" MYSQL_PASSWORD="Azure12345678" MYSQL_DB="todo" \
    --registry-username $regUsername \
    --registry-password "$regPassword"  

# Deploy WebApp
az appservice plan create -n phpplan -g php --is-linux -l westeurope --sku S1 --number-of-workers 1
az webapp create -n mojephptodo -p phpplan -g php -i php/todo 
az webapp config appsettings set -g php -n mojephptodo --settings MYSQL_HOST="mujmysql.mysql.database.azure.com" MYSQL_USERNAME="tomas@mujmysql" MYSQL_PASSWORD="Azure12345678" MYSQL_DB="todo"   
az webapp config container set -n mojephptodo \
    -g php \
    -i mujphpregistr.azurecr.io/php/todo \
    -r "https://mujphpregistr.azurecr.io" \
    -u $regUsername \
    -p $regPassword


# Deploy to AKS
kubectl apply -f kube-service.yaml
kubectl apply -f kube-deploy.yaml

