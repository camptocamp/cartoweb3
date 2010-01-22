      <!-- images -->

      <complexType name="ImagesRequest">
        <all>
          <element name="className" type="xsd:string" nillable="true"/>
          <element name="mainmap" type="types:Image"/>
          <element name="keymap" type="types:Image"/>
          <element name="scalebar" type="types:Image"/>
        </all>
      </complexType>

      <complexType name="ImagesResult">
        <all>
          <element name="className" type="xsd:string" nillable="true"/>
          <element name="mainmap" type="types:Image"/>
          <element name="keymap" type="types:Image"/>
          <element name="scalebar" type="types:Image"/>
        </all>
      </complexType>

      <complexType name="Image">
        <all>
          <element name="isDrawn" type="xsd:boolean"/>
          <element name="path" type="xsd:string" nillable="true"/>
          <element name="width" type="xsd:int"/>
          <element name="height" type="xsd:int"/>
        </all>
      </complexType>
