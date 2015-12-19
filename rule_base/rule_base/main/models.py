from django.db import models

class Bank(models.Model):
    id = models.AutoField(primary_key=True)
    name = models.CharField(max_length=256)
    lowest_credit_score_allowed = models.IntegerField(max_length=10)
    cvr = models.IntegerField(max_length=10)

    class Meta:
        app_label = "rule-base"
        db_table = "banks"
