from main.models import Bank
from django.core import serializers
import json

from django.http import HttpResponse

def get_banks(request, score=0):
    print(score)
    banks = Bank.objects.using('rulebase').filter(lowest_credit_score_allowed__gte=score)
    # data = serializers.serialize("json", banks, fields=('name', 'lowest_credit_score_allowed'))

    # return HttpResponse(data)
    return HttpResponse(json.dumps([{
        'cvr': o.cvr,
        'name': o.name,
        'id': o.id
    } for o in banks]))

def error_500(request):
    return HttpResponse("<h1>Page not found</h1>")
