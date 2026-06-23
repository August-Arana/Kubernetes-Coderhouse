from locust import HttpUser, task, between

class FrontendUser(HttpUser):
    wait_time = between(0.5, 2)

    @task
    def view_products(self):
        self.client.get("/")
