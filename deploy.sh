docker build -t leandroconam/conam-app:latest -t leandroconam/conam-app:$SHA -f ./app/Dockerfile ./app
docker build -t leandroconam/conam-logger:latest -t leandroconam/conam-logger:$SHA -f ./logger/Dockerfile ./logger
docker build -t leandroconam/conam-portal:latest -t leandroconam/conam-portal:$SHA -f ./portal/Dockerfile ./portal

docker push leandroconam/conam-app
docker push leandroconam/conam-logger
docker push leandroconam/conam-portal

kubectl apply -f k8s

kubectl set image deployments/app-deployment conam-app=leandroconam/conam-app:$SHA
kubectl set image deployments/logger-deployment conam-logger=leandroconam/conam-logger:$SHA
kubectl set image deployments/portal-deployment conam-portal=leandroconam/conam-portal:$SHA

echo "done"