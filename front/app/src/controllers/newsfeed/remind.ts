import { Component, View, NgFor, NgIf, NgClass, Observable} from 'angular2/angular2';
import { RouterLink } from "angular2/router";
import { Client } from 'src/services/api';
import { Material } from 'src/directives/material';
import { Activity } from './activity';

@Component({
  selector: 'minds-remind',
  viewBindings: [ Client ],
  properties: ['object']
})
@View({
  templateUrl: 'templates/cards/activity.html',
  directives: [ NgFor, NgIf, NgClass, Material, RouterLink]
})

export class Remind {
  activity : any;
  hideTabs : boolean;

	constructor(public client: Client){
    this.hideTabs = true;
	}

  set object(value: any) {
    this.activity = value;
  }

  toDate(timestamp){
    return new Date(timestamp*1000);
  }
}